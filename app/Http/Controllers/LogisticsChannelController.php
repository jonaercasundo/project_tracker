<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLogisticsIntegrationRequest;
use App\Http\Requests\UpdateLogisticsIntegrationRequest;
use App\Models\LogisticsIntegration;
use App\Models\LogisticsIntegrationCredential;
use App\Services\LogisticsChannelService;
use App\Services\LogisticsDrTrackingService;
use App\Services\LogisticsIntegrationRegistry;
use App\Services\LogisticsWebhookEventService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class LogisticsChannelController extends Controller
{
    public function __construct(
        private LogisticsChannelService $channel,
        private LogisticsDrTrackingService $tracking,
        private LogisticsIntegrationRegistry $integrations,
        private LogisticsWebhookEventService $events
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', LogisticsIntegration::class);
        if (! $this->managementSchemaReady()) {
            return view('logistics-channel.unavailable');
        }
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(['overview', 'integrations', 'api', 'tracking', 'events', 'reconciliation', 'audit'])],
            'integration_id' => ['nullable', 'string', 'max:64'],
            'project_id' => ['nullable', 'integer'],
            'dr_reference' => ['nullable', 'string', 'max:100'],
            'school' => ['nullable', 'string', 'max:100'],
            'event_id' => ['nullable', 'string', 'max:191'],
            'status' => ['nullable', 'string', 'max:32'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);
        $tab = $filters['tab'] ?? 'overview';
        $perPage = (int) ($filters['per_page'] ?? 25);
        $editing = $request->filled('edit')
            ? LogisticsIntegration::query()->with('projects', 'schools', 'credentials')->findOrFail($request->integer('edit'))
            : null;
        $integrations = LogisticsIntegration::query()->withCount([
            'credentials as active_credentials_count' => fn ($query) => $query->where('status', 'active'),
        ])->orderBy('name')->paginate($perPage, ['*'], 'integrations_page')->withQueryString();
        $legacyIntegrations = collect($this->integrations->environmentIntegrationIds())
            ->reject(fn (string $id): bool => LogisticsIntegration::query()->where('integration_id', $id)->exists())
            ->map(fn (string $id): array => [
                'integration_id' => $id,
                'name' => $id,
                'status' => 'active',
                'project_ids' => $this->integrations->scope($id)['project_ids'],
            ])->values();
        $metrics = $this->dashboardMetrics();
        $projects = DB::table('projects')->orderBy('project_name')->get(['project_id', 'project_name']);
        $selectedProjects = array_values(array_filter(array_map('intval', (array) ($request->input('project_ids', [])))));
        if ($selectedProjects === [] && $editing !== null) {
            $selectedProjects = $editing->projects->pluck('project_id')->map(fn ($id): int => (int) $id)->all();
        }
        $schools = $selectedProjects === [] ? collect() : DB::table('school')
            ->whereIn('project_id', $selectedProjects)
            ->orderBy('school_name')
            ->get(['project_id', 'school_id', 'school_name']);
        $editingSchoolTokens = $editing === null ? [] : DB::table('logistics_integration_schools')
            ->where('integration_id', $editing->id)
            ->get(['project_id', 'school_id'])
            ->map(fn (object $row): string => (int) $row->project_id.':'.rtrim(strtr(base64_encode((string) $row->school_id), '+/', '-_'), '='))
            ->all();
        $allSchools = $editing !== null && $editingSchoolTokens === [];
        $eventRows = in_array($tab, ['events', 'reconciliation'], true)
            ? $this->eventRows($filters, $perPage, $tab === 'reconciliation')
            : null;
        $auditRows = $tab === 'audit'
            ? DB::table('logistics_integration_audit_logs')->orderByDesc('id')->paginate($perPage)->withQueryString()
            : null;
        $trackingRows = $tab === 'tracking'
            ? $this->tracking->dashboardPage([
                'project_id' => $filters['project_id'] ?? null,
                'dr_reference' => $filters['dr_reference'] ?? null,
                'school' => $filters['school'] ?? null,
                'status' => $filters['status'] ?? null,
                'date_from' => $filters['from'] ?? null,
                'date_to' => $filters['to'] ?? null,
                'per_page' => $perPage,
                'page' => $request->integer('page', 1),
            ])
            : null;
        $webhookUrl = route('api.v1.logistics.webhooks.delivery-status');

        return view('logistics-channel.index', compact(
            'tab', 'filters', 'integrations', 'legacyIntegrations', 'metrics', 'projects', 'schools',
            'selectedProjects', 'editing', 'editingSchoolTokens', 'allSchools', 'eventRows', 'auditRows', 'trackingRows', 'webhookUrl'
        ));
    }

    public function store(StoreLogisticsIntegrationRequest $request): HttpResponse
    {
        Gate::authorize('create', LogisticsIntegration::class);
        abort_unless($this->managementSchemaReady(), 503, 'Logistics Channel management storage is not ready.');
        $credential = $this->channel->create($request->validated(), (int) $request->user()->user_id, $request->ip());

        return response()->view('logistics-channel.credential', [
            'integration' => $credential['integration'],
            'keyId' => $credential['key_id'],
            'secret' => $credential['secret'],
            'message' => 'Save this signing secret in the Logistics App now. MMC will not show it again.',
            'overlapDays' => null,
        ])->header('Cache-Control', 'private, no-store, max-age=0');
    }

    public function update(UpdateLogisticsIntegrationRequest $request, LogisticsIntegration $integration): RedirectResponse
    {
        Gate::authorize('update', $integration);
        abort_unless($this->managementSchemaReady(), 503, 'Logistics Channel management storage is not ready.');
        $this->channel->update($integration, $request->validated(), (int) $request->user()->user_id, $request->ip());

        return to_route('logistics-channel.index', ['tab' => 'integrations'])->with('status', 'Integration settings saved.');
    }

    public function rotate(Request $request, LogisticsIntegration $integration): HttpResponse
    {
        Gate::authorize('update', $integration);
        abort_unless($this->managementSchemaReady(), 503, 'Logistics Channel management storage is not ready.');
        $validated = $request->validate(['overlap_days' => ['required', 'integer', 'min:1', 'max:30']]);
        $credential = $this->channel->rotate($integration, (int) $request->user()->user_id, $request->ip(), (int) $validated['overlap_days']);

        return response()->view('logistics-channel.credential', [
            'integration' => $integration,
            'keyId' => $credential['key_id'],
            'secret' => $credential['secret'],
            'message' => 'Configure this new key in Logistics before the overlap ends. Previous active keys remain valid for the displayed period.',
            'overlapDays' => $credential['overlap_days'],
        ])->header('Cache-Control', 'private, no-store, max-age=0');
    }

    public function revoke(Request $request, LogisticsIntegration $integration, LogisticsIntegrationCredential $credential): RedirectResponse
    {
        Gate::authorize('update', $integration);
        abort_unless($this->managementSchemaReady(), 503, 'Logistics Channel management storage is not ready.');
        $this->channel->revokeCredential($integration, $credential, (int) $request->user()->user_id, $request->ip());

        return to_route('logistics-channel.index', ['tab' => 'integrations'])->with('status', 'Credential revoked. Requests signed with it will be rejected.');
    }

    public function adopt(Request $request): RedirectResponse
    {
        Gate::authorize('create', LogisticsIntegration::class);
        abort_unless($this->managementSchemaReady(), 503, 'Logistics Channel management storage is not ready.');
        $validated = $request->validate([
            'integration_id' => ['required', 'string', 'max:64', 'regex:/\A[A-Za-z0-9._-]+\z/'],
            'name' => ['required', 'string', 'max:120'],
        ]);
        $result = $this->channel->adoptEnvironmentIntegration(
            $validated['integration_id'],
            $validated['name'],
            (int) $request->user()->user_id,
            $request->ip()
        );

        abort_if($result === null, 404);

        return to_route('logistics-channel.index', ['tab' => 'integrations'])->with('status', 'Environment integration adopted. Existing secret was encrypted without being displayed.');
    }

    public function schools(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', LogisticsIntegration::class);
        abort_unless($this->managementSchemaReady(), 503, 'Logistics Channel management storage is not ready.');
        $validated = $request->validate([
            'project_ids' => ['required', 'array', 'min:1'],
            'project_ids.*' => ['required', 'integer', 'distinct', 'exists:projects,project_id'],
        ]);
        $schools = DB::table('school')->whereIn('project_id', array_map('intval', $validated['project_ids']))
            ->orderBy('school_name')->get(['project_id', 'school_id', 'school_name']);

        return response()->json($schools);
    }

    public function retryEvent(Request $request, int $event): RedirectResponse
    {
        Gate::authorize('create', LogisticsIntegration::class);
        abort_unless($this->managementSchemaReady(), 503, 'Logistics Channel management storage is not ready.');
        abort_unless(Schema::hasTable('logistics_webhook_events'), 404);
        $row = DB::table('logistics_webhook_events')->where('id', $event)->first();
        abort_if($row === null, 404);
        abort_unless($this->events->retryFailedEvent($event), 409, 'Only failed events can be retried.');

        $integration = LogisticsIntegration::query()->where('integration_id', $row->integration_id)->first();
        $this->channel->audit($integration, (int) $request->user()->user_id, 'event_retry_requested', 'webhook_event', (string) $row->id, [
            'external_event_id' => (string) $row->external_event_id,
            'payload_reused' => true,
        ], $request->ip());

        return to_route('logistics-channel.index', ['tab' => 'events'])->with('status', 'Stored event queued for validation again.');
    }

    private function dashboardMetrics(): array
    {
        $registeredIds = LogisticsIntegration::query()->pluck('integration_id')->all();
        $legacyIds = array_values(array_diff($this->integrations->environmentIntegrationIds(), $registeredIds));
        $authorizedProjectIds = DB::table('logistics_integration_projects')->distinct()->pluck('project_id')->map(fn ($id): int => (int) $id)->all();
        foreach ($legacyIds as $legacyId) {
            $authorizedProjectIds = array_merge($authorizedProjectIds, $this->integrations->scope($legacyId)['project_ids']);
        }
        $projectCount = count(array_unique($authorizedProjectIds));
        $tracking = $this->tracking->dashboardPage(['per_page' => 1]);
        $queueConnectionName = config('queue.connections.logistics_webhooks.connection') ?: config('database.default');
        $queueConnection = DB::connection($queueConnectionName);
        $queueTable = config('queue.connections.logistics_webhooks.table', 'jobs');
        $queueReady = Schema::connection($queueConnectionName)->hasTable($queueTable);
        $queueJobs = $queueReady ? $queueConnection->table($queueTable)->where('queue', 'logistics-webhooks') : null;
        $queueDepth = $queueJobs === null ? null : (clone $queueJobs)->whereNull('reserved_at')->count();
        $reservedQueueJobs = $queueJobs === null ? null : (clone $queueJobs)->whereNotNull('reserved_at')->count();
        $lastSuccess = DB::table('logistics_dr_tracking')->max('last_synchronized_at');
        $recentActivity = $lastSuccess !== null && now()->diffInMinutes($lastSuccess) <= 5;

        return [
            'total_integrations' => count($registeredIds) + count($legacyIds),
            'active_integrations' => LogisticsIntegration::query()->where('status', 'active')
                ->whereHas('credentials', fn ($credentials) => $credentials->where('status', 'active')
                    ->where(fn ($expiry) => $expiry->whereNull('expires_at')->orWhere('expires_at', '>', now())))
                ->count() + count($legacyIds),
            'authorized_projects' => $projectCount,
            'received_events' => DB::table('logistics_webhook_events')->count(),
            'synchronized_drs' => DB::table('logistics_dr_tracking')->count(),
            'pending_updates' => $tracking['summary']['pending_updates'],
            'failed_events' => DB::table('logistics_webhook_events')->where('processing_status', 'failed')->count(),
            'reconciliation_required' => $tracking['summary']['reconciliation_required'],
            'last_successful_sync' => $lastSuccess,
            'queue_depth' => $queueDepth,
            'reserved_queue_jobs' => $reservedQueueJobs,
            'worker_activity' => $queueDepth === null ? 'unavailable' : ($queueDepth > 0 && ! $recentActivity ? 'backlog' : ($recentActivity ? 'recent' : 'idle')),
        ];
    }

    private function eventRows(array $filters, int $perPage, bool $reconciliationOnly): mixed
    {
        $query = DB::table('logistics_webhook_events as events')
            ->leftJoin('projects', 'projects.project_id', '=', 'events.resolved_project_id')
            ->leftJoin('school', function ($join): void {
                $join->on('school.school_id', '=', 'events.resolved_school_id')
                    ->on('school.project_id', '=', 'events.resolved_project_id');
            })
            ->select([
                'events.id', 'events.integration_id', 'events.external_event_id', 'events.dr_reference',
                'events.resolved_project_id', 'projects.project_name', 'events.resolved_school_id', 'school.school_name',
                'events.event_type', 'events.event_occurred_at', 'events.event_sequence', 'events.processing_status',
                'events.resolution_status', 'events.reconciliation_status', 'events.processing_attempts', 'events.error_details',
            ]);
        if ($reconciliationOnly) {
            $query->whereNotIn('events.reconciliation_status', ['accepted', 'synchronized', 'pending']);
        }
        if (! empty($filters['integration_id'])) {
            $query->where('events.integration_id', $filters['integration_id']);
        }
        if (! empty($filters['project_id'])) {
            $query->where('events.resolved_project_id', (int) $filters['project_id']);
        }
        if (! empty($filters['dr_reference'])) {
            $query->where('events.dr_reference', 'like', '%'.$filters['dr_reference'].'%');
        }
        if (! empty($filters['event_id'])) {
            $query->where('events.external_event_id', 'like', '%'.$filters['event_id'].'%');
        }
        if (! empty($filters['status'])) {
            $query->where(function ($statusQuery) use ($filters): void {
                $statusQuery->where('events.processing_status', $filters['status'])
                    ->orWhere('events.resolution_status', $filters['status'])
                    ->orWhere('events.reconciliation_status', $filters['status']);
            });
        }
        if (! empty($filters['from'])) {
            $query->whereDate('events.event_occurred_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('events.event_occurred_at', '<=', $filters['to']);
        }

        return $query->orderByDesc('events.id')->paginate($perPage)->withQueryString();
    }

    private function managementSchemaReady(): bool
    {
        foreach ([
            'logistics_integrations',
            'logistics_integration_projects',
            'logistics_integration_schools',
            'logistics_integration_credentials',
            'logistics_integration_audit_logs',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}
