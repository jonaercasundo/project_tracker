<?php

namespace App\Http\Controllers;

use App\Models\DeliveryAuditEvent;
use App\Services\DeliveryAuditTrailService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DeliveryAuditTrailController extends Controller
{
    public function index(Request $request, DeliveryAuditTrailService $auditTrail): View
    {
        $auditTrail->syncRecordedHistory();
        $projectIds = $this->projectIdsFor($request);
        $integrationIds = $this->integrationIdsForProjects($projectIds);
        $filters = $request->validate([
            'project_id' => ['nullable', 'integer'],
            'lot_id' => ['nullable', 'integer'],
            'school' => ['nullable', 'string', 'max:191'],
            'dr_no' => ['nullable', 'string', 'max:191'],
            'event_type' => ['nullable', 'string', 'max:64'],
            'search' => ['nullable', 'string', 'max:191'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'performer' => ['nullable', 'string', 'max:255'],
        ]);
        if (isset($filters['date_from'], $filters['date_to']) && $filters['date_to'] < $filters['date_from']) {
            throw ValidationException::withMessages(['date_to' => 'The end date must be on or after the start date.']);
        }
        if (isset($filters['performer'])
            && ! preg_match('/^(user:\d+|actor:.+)$/', $filters['performer'])) {
            throw ValidationException::withMessages(['performer' => 'Select a listed user or system actor.']);
        }

        $events = $this->scopedEvents($projectIds, $integrationIds)
            ->with('performer:user_id,name')
            ->when($filters['project_id'] ?? null, fn ($query, $value) => $query->where('project_id', $value))
            ->when($filters['lot_id'] ?? null, fn ($query, $value) => $query->where('lot_id', $value))
            ->when($filters['school'] ?? null, fn ($query, $value) => $query->where(fn ($q) => $q->where('school_id', 'like', "%{$value}%")->orWhere('school_name', 'like', "%{$value}%")))
            ->when($filters['dr_no'] ?? null, fn ($query, $value) => $query->where('dr_no', 'like', "%{$value}%"))
            ->when($filters['event_type'] ?? null, fn ($query, $value) => $query->where('event_type', $value))
            ->when($filters['search'] ?? null, function ($query, string $value): void {
                $query->where(function ($searchQuery) use ($value): void {
                    $pattern = '%'.$value.'%';
                    $searchQuery->where('school_id', 'like', $pattern)
                        ->orWhere('school_name', 'like', $pattern)
                        ->orWhere('dr_no', 'like', $pattern)
                        ->orWhere('package_code', 'like', $pattern)
                        ->orWhere('event_type', 'like', $pattern)
                        ->orWhere('source_module', 'like', $pattern)
                        ->orWhere('related_id', 'like', $pattern)
                        ->orWhere('notes', 'like', $pattern)
                        ->orWhereIn('project_id', DB::table('projects')->where('project_name', 'like', $pattern)->select('project_id'))
                        ->orWhereIn('lot_id', DB::table('lot')->where('lot_name', 'like', $pattern)->select('lot_id'));
                });
            })
            ->when($filters['date_from'] ?? null, fn ($query, $value) => $query->whereDate('event_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($query, $value) => $query->whereDate('event_at', '<=', $value))
            ->when($filters['performer'] ?? null, function ($query, string $value): void {
                if (str_starts_with($value, 'user:')) {
                    $query->where('performed_by', substr($value, 5));
                } elseif (str_starts_with($value, 'actor:')) {
                    $query->where('actor_name', substr($value, 6));
                }
            })
            ->orderByDesc('event_at')->orderByDesc('id')->paginate(25)->withQueryString();

        return view('operation.delivery.audit-trail', [
            'events' => $events,
            'filters' => $filters,
            'projects' => DB::table('projects')->whereIn('project_id', $projectIds)->orderBy('project_name')->get(['project_id', 'project_name']),
            'lots' => DB::table('lot')->whereIn('project_id', $projectIds)->orderBy('lot_name')->get(['lot_id', 'lot_name']),
            'performers' => $this->scopedEvents($projectIds, $integrationIds)->select('performed_by', 'actor_name')->distinct()->orderBy('actor_name')->get()
                ->flatMap(fn (DeliveryAuditEvent $event): array => array_filter([
                    $event->performed_by === null ? null : ['value' => 'user:'.$event->performed_by, 'label' => $event->actor_name ?: 'User '.$event->performed_by],
                    $event->actor_name === null ? null : ['value' => 'actor:'.$event->actor_name, 'label' => $event->actor_name],
                ]))
                ->unique('value')->sortBy('label')->values(),
            'eventTypes' => $this->scopedEvents($projectIds, $integrationIds)->select('event_type')->distinct()->orderBy('event_type')->pluck('event_type'),
            'coverage' => $auditTrail->coverage($projectIds, $integrationIds),
        ]);
    }

    public function show(Request $request, int $event): View
    {
        $projectIds = $this->projectIdsFor($request);
        $event = $this->scopedEvents($projectIds, $this->integrationIdsForProjects($projectIds))->findOrFail($event);
        $event->load('performer:user_id,name');

        return view('operation.delivery.audit-trail-show', compact('event'));
    }

    public function correction(Request $request, DeliveryAuditTrailService $auditTrail): RedirectResponse
    {
        $data = $request->validate([
            'event_uuid' => ['required', 'uuid'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $projectIds = $this->projectIdsFor($request);
        $prior = $this->scopedEvents($projectIds, $this->integrationIdsForProjects($projectIds))
            ->where('event_uuid', $data['event_uuid'])->firstOrFail();
        $actor = $request->user();

        $auditTrail->record('correction:'.$prior->event_uuid.':'.$actor->getKey().':'.hash('sha256', $data['reason']), [
            'event_at' => now(),
            'project_id' => $prior->project_id,
            'lot_id' => $prior->lot_id,
            'school_id' => $prior->school_id,
            'school_name' => $prior->school_name,
            'package_code' => $prior->package_code,
            'dr_no' => $prior->dr_no,
            'event_type' => 'correction',
            'previous_status' => null,
            'new_status' => null,
            'quantity' => null,
            'warehouse_id' => $prior->warehouse_id,
            'warehouse_name' => $prior->warehouse_name,
            'performed_by' => $actor->getKey(),
            'actor_name' => $actor->name,
            'source_module' => 'Delivery Audit Trail',
            'integration_id' => $prior->integration_id,
            'related_type' => 'delivery_audit_event',
            'related_id' => $prior->event_uuid,
            'notes' => $data['reason'],
            'metadata' => ['correction_of' => $prior->event_uuid],
        ]);

        return redirect()->route('delivery-audit-trail.show', $prior)->with('status', 'Correction recorded as a new audit event.');
    }

    /** @return array<int, int> */
    private function projectIdsFor(Request $request): array
    {
        $company = $request->user()->currentCompany();

        return DB::table('projects')
            ->when(Schema::hasColumn('projects', 'company_id'), fn ($query) => $query->whereNull('company_id')->orWhere('company_id', $company?->company_id))
            ->pluck('project_id')->map(fn ($id): int => (int) $id)->all();
    }

    /** @param array<int, int> $projectIds @return array<int, string> */
    private function integrationIdsForProjects(array $projectIds): array
    {
        if ($projectIds === [] || ! Schema::hasTable('logistics_integration_projects') || ! Schema::hasTable('logistics_integrations')) {
            return [];
        }

        return DB::table('logistics_integration_projects as mapping')
            ->join('logistics_integrations as integration', 'integration.id', '=', 'mapping.integration_id')
            ->whereIn('mapping.project_id', $projectIds)
            ->pluck('integration.integration_id')->unique()->values()->all();
    }

    /** @param array<int, int> $projectIds @param array<int, string> $integrationIds */
    private function scopedEvents(array $projectIds, array $integrationIds): Builder
    {
        $scope = function ($query) use ($projectIds, $integrationIds): void {
            $query->whereIn('project_id', $projectIds)
                ->orWhere(function ($unlinkedInventory): void {
                    $unlinkedInventory->whereNull('project_id')
                        ->whereIn('source_module', ['Inventory', 'Billing Sync']);
                });
            if ($integrationIds !== []) {
                $query->orWhereIn('integration_id', $integrationIds);
            }
        };

        return DeliveryAuditEvent::query()->where(function ($query) use ($scope): void {
            $scope($query);
        })->orWhere(function ($query) use ($scope): void {
            $query->where('event_type', 'correction')
                ->whereIn('related_id', DeliveryAuditEvent::query()->where(function ($parent) use ($scope): void {
                    $scope($parent);
                })->select('event_uuid'));
        });
    }
}
