<?php

namespace App\Services;

use App\Models\BiddingKeyStage;
use App\Models\BiddingPackage;
use App\Models\ProjectInformation;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BiddingSchoolService
{
    /** @param list<int> $lotIds */
    public function guardDeletion(array $lotIds): void
    {
        if (BiddingPackage::query()->whereHas('keyStage', fn ($q) => $q->whereIn('lot_id', $lotIds))->exists()) {
            throw ValidationException::withMessages(['lots' => 'Remove school assignments and packages before deleting their lot or bidding document.']);
        }
    }

    /** @param array<string, mixed> $data */
    public function assertFresh(ProjectInformation $project, array $data): void
    {
        if (isset($data['school_plan'])) {
            $draft = $this->draft($data['school_token'] ?? '', $project->id);
            if ($draft['base'] !== $this->fingerprint($project)) {
                throw ValidationException::withMessages(['school_plan' => 'School assignments changed in another session. Reload before saving.']);
            }
        }
    }

    /** @return array<string, mixed> */
    public function plan(?ProjectInformation $project): array
    {
        if ($project === null) {
            return ['packages' => []];
        }
        $packages = BiddingPackage::query()->whereHas('keyStage', fn ($q) => $q->where('project_id', $project->id))
            ->with(['keyStage.lot', 'items'])->withCount('assignments')->orderBy('id')->get();

        return ['packages' => $packages->map(fn ($p) => [
            'key' => $p->key, 'lot_no' => $p->keyStage->lot->lot_no, 'stage' => $p->keyStage->name,
            'code' => $p->code, 'grade_alias' => $p->grade_alias, 'quantity_basis' => $p->quantity_basis,
            'cartons' => $p->cartons, 'cbm' => $p->cbm, 'volumetric_weight' => $p->volumetric_weight,
            'gross_weight' => $p->gross_weight, 'assignments_count' => $p->assignments_count,
            'items' => $p->items->map(fn ($i) => $i->only(['catalog_item_id', 'item_description', 'unit', 'quantity', 'unit_cost', 'brand', 'remarks']))->all(),
        ])->all()];
    }

    /** @return array<string, mixed> */
    public function validatePlan(string $json): array
    {
        $plan = json_decode($json, true);
        if (! is_array($plan)) {
            throw ValidationException::withMessages(['school_plan' => 'The school package configuration is invalid.']);
        }
        $decimal = ['required', 'numeric', 'regex:/^\d{1,9}(?:\.\d{1,2})?$/'];
        $rules = [
            'packages' => ['present', 'array', 'max:200'],
            'packages.*.key' => ['required', 'uuid', 'distinct'],
            'packages.*.lot_no' => ['required', 'string', 'max:50'],
            'packages.*.stage' => ['required', 'string', 'max:255'],
            'packages.*.code' => ['required', 'regex:/^[A-Za-z0-9_-]{1,50}$/'],
            'packages.*.grade_alias' => ['required', 'string', 'max:100'],
            'packages.*.quantity_basis' => ['required', 'in:per_school,lot_total'],
            'packages.*.cartons' => ['required', 'integer', 'min:0', 'max:1000000'],
            'packages.*.items' => ['required', 'array', 'min:1', 'max:100'],
            'packages.*.items.*.catalog_item_id' => ['required', 'integer'],
            'packages.*.items.*.item_description' => ['nullable', 'string', 'max:10000'],
            'packages.*.items.*.unit' => ['required', 'string', 'max:50'],
            'packages.*.items.*.quantity' => $decimal,
            'packages.*.items.*.unit_cost' => $decimal,
            'packages.*.items.*.brand' => ['nullable', 'string', 'max:255'],
            'packages.*.items.*.remarks' => ['nullable', 'string', 'max:10000'],
        ];
        foreach (['cbm', 'volumetric_weight', 'gross_weight'] as $field) {
            $rules['packages.*.'.$field] = ['required', 'numeric', 'min:0', 'max:999999999', 'regex:/^\d+(?:\.\d{1,4})?$/'];
        }
        $data = Validator::make($plan, $rules)->validate();
        $identities = [];
        $aliases = [];
        $items = collect($data['packages'])->flatMap(fn ($p) => $p['items']);
        if ($items->count() > 1000) {
            throw ValidationException::withMessages(['school_plan' => 'Configure at most 1,000 package items.']);
        }
        $catalog = DB::table('items')->whereIn('id', $items->pluck('catalog_item_id')->unique())->pluck('id')->all();
        foreach ($data['packages'] as $package) {
            $identity = mb_strtolower($package['lot_no'].'|'.$package['stage'].'|'.$package['code']);
            $alias = mb_strtolower($package['lot_no'].'|'.$package['grade_alias']);
            if (isset($identities[$identity]) || (isset($aliases[$alias]) && $aliases[$alias] !== $package['stage'])) {
                throw ValidationException::withMessages(['school_plan' => 'Package codes must be unique within a stage; a grade alias must identify one stage within a lot.']);
            }
            $identities[$identity] = true;
            $aliases[$alias] = $package['stage'];
            foreach ($package['items'] as $item) {
                if (! in_array((int) $item['catalog_item_id'], $catalog)) {
                    throw ValidationException::withMessages(['school_plan' => 'A selected catalog item no longer exists.']);
                }
            }
        }

        return $data;
    }

    /** @return list<array{school_id: string, package_key: string}> */
    public function rows(?ProjectInformation $project): array
    {
        if ($project === null) {
            return [];
        }

        return DB::table('bidding_school_assignments as a')->join('bidding_packages as p', 'p.id', '=', 'a.package_id')
            ->join('keystages as k', 'k.id', '=', 'p.keystage_id')->where('k.project_id', $project->id)
            ->orderBy('a.id')->get(['a.school_id', 'p.key as package_key'])->map(fn ($row) => (array) $row)->all();
    }

    public function fingerprint(?ProjectInformation $project): string
    {
        return hash('sha256', json_encode([$project?->school_revision, $this->plan($project), $this->rows($project)]));
    }

    /** @param array<string, mixed> $draft */
    public function putDraft(array $draft): string
    {
        $token = (string) Str::uuid();
        Cache::put('bidding-schools:'.$token, $draft, now()->addHours(8));

        return $token;
    }

    /** @return array<string, mixed> */
    public function draft(string $token, ?int $projectId = null): array
    {
        $draft = Cache::get('bidding-schools:'.$token);
        if (! is_array($draft) || $draft['user'] !== auth()->id() || $draft['company'] !== session('company_id') || $draft['project'] !== $projectId) {
            throw ValidationException::withMessages(['school_token' => 'This school draft is unavailable or expired. Reload and preview the assignments again.']);
        }

        return $draft;
    }

    /** @return array<string, mixed> */
    public function begin(?ProjectInformation $project): array
    {
        return ['user' => auth()->id(), 'company' => session('company_id'), 'project' => $project?->id,
            'base' => $this->fingerprint($project), 'rows' => $this->rows($project), 'history' => []];
    }

    /** @param array<string, mixed> $data */
    public function sync(ProjectInformation $project, array $data, bool $creating): void
    {
        if (! isset($data['school_plan'])) {
            return;
        }
        $plan = $this->validatePlan($data['school_plan']);
        $draft = $this->draft($data['school_token'] ?? '', $creating ? null : $project->id);
        $lots = $project->lots()->get()->keyBy('lot_no');
        $old = BiddingPackage::query()->whereHas('keyStage', fn ($q) => $q->where('project_id', $project->id))->with('items')->get()->keyBy('key');
        $protected = DB::table('bidding_school_assignments')->whereIn('package_id', $old->pluck('id'))->whereNotNull('delivery_id')->exists();
        if ($protected && ($this->validatePlan(json_encode($this->plan($project))) != $plan || $draft['rows'] !== $this->rows($project))) {
            throw ValidationException::withMessages(['school_plan' => 'Assignments linked to delivery records cannot be changed here.']);
        }
        if ($protected) {
            return;
        }
        $keys = array_column($plan['packages'], 'key');
        foreach ($draft['rows'] as $row) {
            if (! in_array($row['package_key'], $keys, true)) {
                throw ValidationException::withMessages(['school_plan' => 'Remove or reassign schools before removing their package.']);
            }
        }
        $counts = array_count_values(array_column($draft['rows'], 'package_key'));
        $packageIds = [];
        foreach ($old as $package) {
            $package->update(['code' => '__sync_'.Str::uuid()]);
        }
        foreach ($plan['packages'] as $definition) {
            $lot = $lots->get($definition['lot_no']);
            if ($lot === null) {
                throw ValidationException::withMessages(['school_plan' => 'Every package must belong to a submitted lot.']);
            }
            $stage = BiddingKeyStage::query()->firstOrCreate(['project_id' => $project->id, 'lot_id' => $lot->id, 'delivery_address_id' => null, 'name' => $definition['stage']]);
            $package = $old->get($definition['key']) ?? new BiddingPackage;
            if (! $package->exists && BiddingPackage::query()->where('key', $definition['key'])->exists()) {
                throw ValidationException::withMessages(['school_plan' => 'This package belongs to another bidding document.']);
            }
            $package->fill(collect($definition)->only(['key', 'code', 'grade_alias', 'quantity_basis', 'cartons', 'cbm', 'volumetric_weight', 'gross_weight'])->all());
            $package->keystage_id = $stage->id;
            $package->save();
            $packageIds[$package->key] = $package->id;
            $package->items()->delete();
            foreach ($definition['items'] as $item) {
                $amount = BigDecimal::of($item['quantity'])->multipliedBy($item['unit_cost']);
                if ($definition['quantity_basis'] === 'per_school') {
                    $amount = $amount->multipliedBy($counts[$package->key] ?? 0);
                }
                $package->items()->create(array_merge($item, ['item_description' => $item['item_description'] ?? '', 'lot_id' => $lot->id, 'keystage_id' => $stage->id,
                    'total_amount' => BiddingService::amount($amount->toScale(2, RoundingMode::HalfUp))]));
            }
        }
        $existing = DB::table('bidding_school_assignments')->whereIn('package_id', $old->pluck('id'))->get()->keyBy(fn ($r) => $r->package_id.'|'.$r->school_id);
        $retained = [];
        $inserts = [];
        foreach ($draft['rows'] as $row) {
            $packageId = $packageIds[$row['package_key']];
            $saved = $existing->get($packageId.'|'.$row['school_id']);
            if ($saved !== null) {
                $retained[$saved->id] = true;
            } else {
                $inserts[] = ['package_id' => $packageId, 'school_id' => $row['school_id'], 'created_at' => now(), 'updated_at' => now()];
            }
        }
        $removed = $existing->filter(fn ($saved) => ! isset($retained[$saved->id]))->pluck('id')->all();
        foreach (array_chunk($removed, 500) as $chunk) {
            DB::table('bidding_school_assignments')->whereIn('id', $chunk)->delete();
        }
        foreach (array_chunk($inserts, 500) as $chunk) {
            DB::table('bidding_school_assignments')->insert($chunk);
        }
        foreach ($old as $key => $package) {
            if (! in_array($key, $keys, true)) {
                $package->items()->delete();
                $package->delete();
            }
        }
        foreach ($draft['history'] as $history) {
            DB::table('bidding_imports')->insert(array_merge($history, ['project_id' => $project->id, 'user_id' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]));
        }
        $project->increment('school_revision');
    }
}
