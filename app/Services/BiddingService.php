<?php

namespace App\Services;

use App\Models\BiddingDeliveryAddress;
use App\Models\BiddingKeyStage;
use App\Models\New\Item;
use App\Models\ProjectInformation;
use App\Models\ProjectItem;
use App\Models\ProjectLot;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BiddingService
{
    public const MAX_AMOUNT = '9999999999999.99';

    /** @param array<string, mixed> $data */
    public function store(array $data): ProjectInformation
    {
        return $this->saveTransaction(function () use ($data): ProjectInformation {
            $project = ProjectInformation::create(array_merge([
                'procuring_entity' => '', 'approved_budget_contract_abc' => '0.00', 'status' => 'Draft',
            ], $this->parentAttributes($data)));
            $this->syncLots($project, $data['lots']);
            $this->refreshTotal($project);

            return $project;
        }, $data);
    }

    /** @param array<string, mixed> $data */
    public function update(ProjectInformation $bidding, array $data): ProjectInformation
    {
        return $this->saveTransaction(function () use ($bidding, $data): ProjectInformation {
            $project = ProjectInformation::query()->whereKey($bidding->getKey())->lockForUpdate()->firstOrFail();
            $project->update($this->parentAttributes($data));
            if (array_key_exists('lots', $data)) {
                $this->syncLots($project, $data['lots']);
            }
            $this->refreshTotal($project);

            return $project;
        }, $data, $bidding->getKey());
    }

    /** @param callable(): ProjectInformation $operation @param array<string, mixed> $data */
    private function saveTransaction(callable $operation, array $data, ?int $id = null): ProjectInformation
    {
        try {
            return DB::transaction($operation, 3);
        } catch (UniqueConstraintViolationException $exception) {
            if (isset($data['project_id']) && ProjectInformation::query()->where('project_id', $data['project_id'])->when($id !== null, fn ($query) => $query->whereKeyNot($id))->exists()) {
                throw ValidationException::withMessages(['project_id' => 'A bidding document with this Project ID already exists.']);
            }

            throw $exception;
        }
    }

    public function delete(ProjectInformation $bidding): void
    {
        DB::transaction(function () use ($bidding): void {
            $project = ProjectInformation::query()->whereKey($bidding->getKey())->lockForUpdate()->firstOrFail();
            if (class_exists(BiddingDocumentService::class)) {
                app(BiddingDocumentService::class)->deleteBiddingFiles($project);
            }
            $project->keyStages()->delete();
            $project->delete();
        });
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function parentAttributes(array $data): array
    {
        $attributes = Arr::only($data, ['project_code', 'project_name', 'project_id', 'procuring_entity',
            'approved_budget_contract_abc', 'delivery_period', 'date_of_bid_opening', 'status',
            'prepared_by', 'prepared_date', 'verified_by', 'notes_special_condition']);
        if (array_key_exists('date_of_pre_bid_conference', $data)) {
            $attributes['pre_bid_conf'] = $data['date_of_pre_bid_conference'];
        }
        if (array_key_exists('approved_budget_contract_abc', $attributes)) {
            $attributes['approved_budget_contract_abc'] = self::amount((string) ($attributes['approved_budget_contract_abc'] ?? '0'));
        }
        if (array_key_exists('procuring_entity', $attributes)) {
            $attributes['procuring_entity'] ??= '';
        }

        return $attributes;
    }

    /** @param array<array-key, array<string, mixed>> $rows */
    private function syncLots(ProjectInformation $project, array $rows): void
    {
        foreach ($project->lots()->get() as $lot) {
            $lot->update(['lot_no' => '__sync_'.Str::uuid()]);
        }
        $this->reconcile($project->lots(), $rows, function (ProjectLot $lot, array $data, string $path) use ($project): void {
            $attributes = ['lot_no' => $data['lot_no']];
            if (array_key_exists('country_code', $data) || ! $lot->exists) {
                $attributes['country'] = 'Philippines';
            }
            $hasLocation = collect(['region_code', 'province_code', 'city_code', 'barangay_code'])->contains(fn (string $field): bool => ! empty($data[$field]));
            foreach (['region_code' => 'region', 'province_code' => 'province', 'city_code' => 'city_municipality', 'barangay_code' => 'barangay'] as $codeField => $nameField) {
                if (! array_key_exists($codeField, $data)) {
                    continue;
                }
                $code = $data[$codeField];
                $attributes[$codeField] = $code;
                if ($code !== null && $code !== '') {
                    $attributes[$nameField] = DB::table('psgc')->where('psgc_code', $code)->value('name');
                } elseif ($hasLocation || $lot->{$codeField} !== null || ! $lot->exists) {
                    $attributes[$nameField] = null;
                }
            }
            $lot->fill($attributes)->save();
            if (array_key_exists('addresses', $data)) {
                $this->reconcile($lot->addresses()->where('project_id', $project->id), $data['addresses'], function (BiddingDeliveryAddress $address, array $addressData, string $addressPath) use ($lot, $project): void {
                    $address->fill(['project_id' => $project->id, 'delivery_address' => $addressData['delivery_address']])->save();
                    if (array_key_exists('keystages', $addressData)) {
                        $this->reconcile($address->keystages()->where('project_id', $project->id)->where('lot_id', $lot->id), $addressData['keystages'], function (BiddingKeyStage $stage, array $stageData, string $stagePath) use ($lot, $project): void {
                            $stage->fill(['project_id' => $project->id, 'lot_id' => $lot->id, 'name' => $stageData['name']])->save();
                            if (array_key_exists('items', $stageData)) {
                                $this->reconcile($stage->items()->where('lot_id', $lot->id), $stageData['items'], function (ProjectItem $item, array $itemData, string $itemPath) use ($lot, $stage): void {
                                    $this->saveItem($item, $itemData, $lot, $stage, $itemPath);
                                }, $stagePath.'.items');
                            }
                        }, $addressPath.'.keystages');
                    }
                }, $path.'.addresses');
            }
            if (array_key_exists('legacy_items', $data)) {
                $this->reconcile($lot->legacyItems(), $data['legacy_items'], function (ProjectItem $item, array $itemData, string $itemPath) use ($lot): void {
                    $this->saveItem($item, $itemData, $lot, null, $itemPath);
                }, $path.'.legacy_items');
            }
            $lot->items()->update(['item_no' => null]);
            foreach ($lot->items()->orderBy('id')->get() as $index => $item) {
                $item->update(['item_no' => $index + 1]);
            }
        }, 'lots');
    }

    /**
     * @param array<array-key, array<string, mixed>> $rows
     * @param callable(Model, array<string, mixed>, string): void $persist
     */
    private function reconcile(HasMany $relation, array $rows, callable $persist, string $path): void
    {
        $retained = [];
        foreach ($rows as $index => $data) {
            if (isset($data['id'])) {
                $model = (clone $relation)->whereKey($data['id'])->first();
                if ($model === null || in_array($model->getKey(), $retained, true)) {
                    throw ValidationException::withMessages([$path.'.'.$index.'.id' => 'This row does not belong to its submitted parent.']);
                }
            } else {
                $model = $relation->make();
            }
            $persist($model, $data, $path.'.'.$index);
            $retained[] = $model->getKey();
        }
        foreach ((clone $relation)->whereNotIn($relation->getRelated()->getQualifiedKeyName(), $retained)->get() as $removed) {
            $this->deleteChild($removed);
        }
    }

    private function deleteChild(Model $model): void
    {
        if ($model instanceof ProjectLot) {
            foreach ($model->addresses()->where('project_id', $model->project_id)->get() as $address) {
                $this->deleteChild($address);
            }
            BiddingKeyStage::query()->where('project_id', $model->project_id)->where('lot_id', $model->id)->delete();
        } elseif ($model instanceof BiddingDeliveryAddress) {
            foreach ($model->keystages()->where('project_id', $model->project_id)->where('lot_id', $model->lot_id)->get() as $stage) {
                $this->deleteChild($stage);
            }
        } elseif ($model instanceof BiddingKeyStage) {
            $model->items()->where('lot_id', $model->lot_id)->delete();
        }
        $model->delete();
    }

    /** @param array<string, mixed> $data */
    private function saveItem(ProjectItem $item, array $data, ProjectLot $lot, ?BiddingKeyStage $stage, string $path): void
    {
        $catalog = isset($data['catalog_item_id']) ? Item::findOrFail($data['catalog_item_id']) : null;
        $quantity = self::amount((string) $data['quantity']);
        $cost = array_key_exists('unit_cost', $data) ? $data['unit_cost'] : $item->unit_cost;
        if ($cost === null) {
            if (! $item->exists || $stage !== null || $item->unit_cost !== null || ! BigDecimal::of($quantity)->isEqualTo($item->quantity ?? '0')) {
                throw ValidationException::withMessages([$path.'.unit_cost' => 'Provide a unit cost before changing this legacy quantity.']);
            }
            $total = $item->total_amount;
        } else {
            $cost = self::amount((string) $cost);
            $total = self::amount(BigDecimal::of($quantity)->multipliedBy($cost)->toScale(2, RoundingMode::HalfUp));
        }
        $attributes = Arr::only($data, ['catalog_item_id', 'item_description', 'unit', 'brand', 'remarks']);
        $attributes['item_description'] = $attributes['item_description'] ?? $item->item_description ?? $catalog?->description ?? $catalog?->item_name;
        $attributes['unit'] = $attributes['unit'] ?? $item->unit ?? $catalog?->unit;
        if (mb_strlen($attributes['unit'] ?? '') > 50) {
            throw ValidationException::withMessages([$path.'.unit' => 'Unit may not exceed 50 characters.']);
        }
        $item->fill(array_merge($attributes, ['lot_id' => $lot->id, 'keystage_id' => $stage?->id,
            'item_no' => null, 'quantity' => $quantity, 'unit_cost' => $cost, 'total_amount' => $total]))->save();
    }

    public static function amount(string|int|BigDecimal $value): string
    {
        $amount = BigDecimal::of($value)->toScale(2, RoundingMode::Unnecessary);
        if ($amount->isNegative() || $amount->isGreaterThan(self::MAX_AMOUNT)) {
            throw ValidationException::withMessages(['lots' => 'A calculated amount exceeds the DECIMAL(15,2) range.']);
        }

        return (string) $amount;
    }

    public function calculatedTotal(ProjectInformation $bidding): string
    {
        $total = BigDecimal::of('0');
        foreach ($bidding->items()->pluck('project_items.total_amount') as $amount) {
            $total = $total->plus($amount ?? '0');
        }

        return self::amount($total);
    }

    private function refreshTotal(ProjectInformation $bidding): void
    {
        $bidding->update(['calculated_total' => $this->calculatedTotal($bidding)]);
    }

    /** @return array<int, array<string, mixed>> */
    public function formLots(ProjectInformation $bidding): array
    {
        $bidding->load('lots.addresses.keystages.items', 'lots.legacyItems', 'lots.items');

        return $bidding->lots->map(function (ProjectLot $lot): array {
            $row = Arr::only($lot->toArray(), ['id', 'lot_no', 'region_code', 'province_code', 'city_code', 'barangay_code']);
            $row['country_code'] = 'PH';
            $row['legacy_delivery_address'] = $lot->delivery_address;
            $row['legacy_location'] = implode(', ', array_filter([$lot->region, $lot->province, $lot->city_municipality, $lot->barangay]));
            $row['addresses'] = $lot->addresses->map(fn (BiddingDeliveryAddress $address): array => [
                'id' => $address->id, 'delivery_address' => $address->delivery_address ?? $address->delivery_address_otherInformation,
                'keystages' => $address->keystages->map(fn (BiddingKeyStage $stage): array => [
                    'id' => $stage->id, 'name' => $stage->name ?? 'Legacy stage', 'items' => $stage->items->toArray(),
                ])->all(),
            ])->all();
            $row['legacy_items'] = $lot->legacyItems->toArray();

            return $row;
        })->all();
    }
}
