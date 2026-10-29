<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeliveryMonitoringDetailsRequest;
use App\Http\Requests\DeliveryMonitoringLocationsRequest;
use App\Http\Requests\DeliveryMonitoringLogisticsRequest;
use App\Http\Requests\DeliveryMonitoringRequest;
use App\Jobs\SyncProjectBillingOperationalStages;
use App\Services\BillingSheetSyncService;
use App\Services\GoogleSheetsApiClient;
use App\Services\LogisticsDrTrackingService;
use App\Services\ProjectDeliveryProgressService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliveryMonitoringController extends Controller
{
    public function __construct(
        private ProjectDeliveryProgressService $progress,
        private LogisticsDrTrackingService $logisticsTracking
    ) {}

    public function logistics(DeliveryMonitoringLogisticsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $summaryOnly = (bool) ($filters['summary_only'] ?? false);
        unset($filters['summary_only']);

        if ($summaryOnly) {
            $result = ['summary' => $this->logisticsTracking->dashboardSummary($filters)];

            return response()->json([
                'html' => view('operation.delivery.partials.monitoring-logistics-tracking', ['logistics' => $result])->render(),
                'summary' => $result['summary'],
            ])->header('Cache-Control', 'private, no-store');
        }

        $result = $this->logisticsTracking->dashboardPage($filters);

        return response()->json([
            'html' => view('operation.delivery.partials.monitoring-logistics-tracking', ['logistics' => $result])->render(),
            'summary' => $result['summary'],
            'pagination' => [
                'current_page' => $result['rows']->currentPage(),
                'last_page' => $result['rows']->lastPage(),
                'per_page' => $result['rows']->perPage(),
                'total' => $result['rows']->total(),
            ],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function locations(DeliveryMonitoringLocationsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $level = $filters['level'];
        $query = DB::table('school')->whereNotNull($level)->where($level, '<>', '');
        if ($level !== 'region' && ! empty($filters['region'])) {
            $query->where('region', $filters['region']);
        }
        if ($level === 'municipality' && ! empty($filters['division'])) {
            $query->where('division', $filters['division']);
        }

        return response()->json(['options' => $query->distinct()->orderBy($level)->pluck($level)])
            ->header('Cache-Control', 'private, no-store');
    }

    public function details(DeliveryMonitoringDetailsRequest $request, int $project): JsonResponse
    {
        $filters = array_filter($request->validated(), fn (mixed $value): bool => $value !== null && $value !== '');
        $filters['project_id'] = $project;
        $section = $filters['section'];
        $records = $this->progress->detailRecords($filters, $section, (int) ($filters['per_page'] ?? 25), (int) ($filters['page'] ?? 1));

        return response()->json([
            'section' => $section,
            'records' => $records,
            'html' => view('operation.delivery.partials.monitoring-details', compact('records', 'section'))->render(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function lotBilling(int $project): JsonResponse
    {
        abort_unless(DB::table('projects')->where('project_id', $project)->exists(), 404, 'Project not found.');

        $lotBillingProgress = $this->progress->lotBillingProgressForProject($project);
        $projectData = ['lot_billing_progress' => $lotBillingProgress];

        return response()->json([
            'lot_billing_progress' => $lotBillingProgress,
            'html' => view('operation.delivery.partials.monitoring-lot-billing', ['project' => $projectData])->render(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function syncBilling(int $project): JsonResponse
    {
        $user = Auth::user();
        abort_unless(DB::table('projects')->where('project_id', $project)->exists(), 404, 'Project not found.');

        $run = null;
        try {
            $run = DB::transaction(function () use ($project, $user): array {
                $projectRecord = DB::table('projects')->where('project_id', $project)->lockForUpdate()->first();
                if ($projectRecord === null) {
                    return ['id' => 0, 'created' => false];
                }

                $activeRun = DB::table('billing_sync_runs')
                    ->where('project_id', $project)
                    ->whereIn('status', ['queued', 'running'])
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if ($activeRun !== null) {
                    return ['id' => (int) $activeRun->id, 'created' => false];
                }

                $now = now();
                $runId = DB::table('billing_sync_runs')->insertGetId([
                    'project_id' => $project,
                    'user_id' => (int) $user->getAuthIdentifier(),
                    'user_name' => (string) $user->name,
                    'status' => 'queued',
                    'message' => 'Sync Billing is queued and will update inventory and delivery stages together.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                return ['id' => (int) $runId, 'created' => true];
            }, 3);

            if ($run['created']) {
                if (app()->environment('testing')) {
                    SyncProjectBillingOperationalStages::dispatchSync($run['id']);
                } else {
                    SyncProjectBillingOperationalStages::dispatch($run['id'])->afterCommit();
                }
            }
        } catch (Throwable $exception) {
            if ($run !== null && $run['created']) {
                DB::table('billing_sync_runs')->where('id', $run['id'])->update([
                    'status' => 'failed',
                    'message' => 'Sync Billing could not be queued. No changes were made. Check that the billing_sync queue worker is running, then try again.',
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Log::error('Sync Billing failed.', [
                'project_id' => $project,
                'user_id' => Auth::id(),
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Sync Billing could not be queued. No changes were made. Check that the database queue worker is running, then try again.',
            ], 503);
        }

        abort_if($run['id'] === 0, 404, 'Project not found.');

        if (app()->environment('testing')) {
            $finishedRun = DB::table('billing_sync_runs')->where('id', $run['id'])->first();
            if ($finishedRun->status === 'failed') {
                return response()->json(['message' => $finishedRun->message], 422);
            }

            return response()->json([
                'synced_dr_count' => (int) $finishedRun->synced_dr_count,
                'skipped_dr_count' => (int) $finishedRun->skipped_dr_count,
                'message' => $finishedRun->message,
            ])->header('Cache-Control', 'private, no-store');
        }

        return response()->json([
            'run_id' => $run['id'],
            'status_url' => route('deliveries.monitoring.sync-billing.status', ['project' => $project, 'run' => $run['id']]),
            'message' => $run['created'] ? 'Sync Billing queued. The page will update when it finishes.' : 'A Sync Billing run is already in progress for this project.',
        ], 202)->header('Cache-Control', 'private, no-store');
    }

    public function syncSpreadsheet(Request $request, int $project, BillingSheetSyncService $billingSheetSync, GoogleSheetsApiClient $sheets): JsonResponse
    {
        $fields = $request->validate([
            'school_id' => ['required', 'string', 'max:255'],
            'date_received' => ['required', 'date_format:Y-m-d'],
            'received_by' => ['required', 'string', 'max:255'],
            'inspection_1' => ['required', 'string', 'max:255'],
            'inspection_2' => ['required', 'string', 'max:255'],
            'date_checking' => ['required', 'date_format:Y-m-d'],
            'date_encoding' => ['required', 'date_format:Y-m-d'],
        ]);
        $projectExists = DB::table('projects')->where('project_id', $project)->exists();
        abort_unless($projectExists, 404, 'Project not found.');

        $schoolId = $fields['school_id'];
        unset($fields['school_id']);
        $result = $billingSheetSync->syncProjectSpreadsheet($project, $schoolId, (string) Auth::user()->name, $fields, $sheets);

        return response()->json($result, $result['status'] === 'success' ? 200 : 422)
            ->header('Cache-Control', 'private, no-store');
    }

    public function spreadsheetSyncSchools(int $project, BillingSheetSyncService $billingSheetSync, GoogleSheetsApiClient $sheets): JsonResponse
    {
        abort_unless(DB::table('projects')->where('project_id', $project)->exists(), 404, 'Project not found.');
        $result = $billingSheetSync->spreadsheetSyncSchools($project, $sheets);

        return response()->json($result, $result['status'] === 'success' ? 200 : 503)
            ->header('Cache-Control', 'private, no-store');
    }

    public function syncBillingStatus(int $project, int $run): JsonResponse
    {
        $syncRun = DB::table('billing_sync_runs')
            ->where('id', $run)
            ->where('project_id', $project)
            ->first();

        abort_if($syncRun === null, 404, 'Billing sync run not found.');

        return response()->json([
            'status' => $syncRun->status,
            'message' => $syncRun->message,
            'synced_dr_count' => (int) $syncRun->synced_dr_count,
            'skipped_dr_count' => (int) $syncRun->skipped_dr_count,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function index(DeliveryMonitoringRequest $request): View|JsonResponse
    {
        $filters = array_filter($request->validated(), fn (mixed $value): bool => $value !== null && $value !== '');
        $filters['active_only'] ??= 0;
        if ($request->expectsJson()) {
            $report = $this->progress->report([...$filters, 'defer_lot_billing' => true]);

            return response()->json($report + [
                'summary_html' => view('operation.delivery.partials.monitoring-results', ['report' => $report])->render(),
                'projects_html' => view('operation.delivery.partials.monitoring-projects', ['report' => $report])->render(),
            ])->header('Cache-Control', 'private, no-store');
        }

        $report = [
            'projects' => collect(),
            'summary' => ['projects_count' => 0],
            'empty_reason' => isset($filters['project_id']) ? 'loading' : 'choose_project',
        ];

        return view('operation.delivery.monitoring', [
            'report' => $report,
            'filters' => $filters,
            'projects' => DB::table('projects')->orderBy('project_name')->get(['project_id', 'project_name']),
            'locations' => DB::table('school')->whereNotNull('region')->where('region', '<>', '')->distinct()->orderBy('region')->pluck('region'),
            'years' => DB::table('deliveries')->whereNotNull('delivery_date')->selectRaw(DB::connection()->getDriverName() === 'sqlite' ? "CAST(strftime('%Y', delivery_date) AS INTEGER) as year" : 'YEAR(delivery_date) as year')->distinct()->orderByDesc('year')->pluck('year'),
        ]);
    }
}
