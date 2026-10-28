<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeliveryMonitoringDetailsRequest;
use App\Http\Requests\DeliveryMonitoringLocationsRequest;
use App\Http\Requests\DeliveryMonitoringLogisticsRequest;
use App\Http\Requests\DeliveryMonitoringRequest;
use App\Services\BillingOperationalSyncService;
use App\Services\LogisticsDrTrackingService;
use App\Services\ProjectDeliveryProgressService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class DeliveryMonitoringController extends Controller
{
    public function __construct(
        private ProjectDeliveryProgressService $progress,
        private LogisticsDrTrackingService $logisticsTracking
    ) {}

    public function logistics(DeliveryMonitoringLogisticsRequest $request): JsonResponse
    {
        $result = $this->logisticsTracking->dashboardPage($request->validated());

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

    public function syncBilling(int $project, BillingOperationalSyncService $sync): JsonResponse
    {
        try {
            $result = $sync->syncProject($project, (int) Auth::id(), (string) Auth::user()->name);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage().' No changes were saved.',
            ], 422);
        } catch (Throwable $exception) {
            Log::error('Sync Billing failed.', [
                'project_id' => $project,
                'user_id' => Auth::id(),
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Sync Billing could not complete. No changes were saved. Please try again or contact an administrator.',
            ], 500);
        }

        return response()->json($result)->header('Cache-Control', 'private, no-store');
    }

    public function index(DeliveryMonitoringRequest $request): View|JsonResponse
    {
        $filters = array_filter($request->validated(), fn (mixed $value): bool => $value !== null && $value !== '');
        $filters['active_only'] ??= 0;
        $report = $this->progress->report([...$filters, 'defer_lot_billing' => true]);
        if ($request->expectsJson()) {
            return response()->json($report + [
                'summary_html' => view('operation.delivery.partials.monitoring-results', ['report' => $report])->render(),
                'projects_html' => view('operation.delivery.partials.monitoring-projects', ['report' => $report])->render(),
            ])->header('Cache-Control', 'private, no-store');
        }

        $logistics = $this->logisticsTracking->dashboardPage(['per_page' => 25, 'page' => 1]);

        return view('operation.delivery.monitoring', [
            'report' => $report,
            'logistics' => $logistics,
            'filters' => $filters,
            'projects' => DB::table('projects')->orderBy('project_name')->get(['project_id', 'project_name']),
            'logisticsIntegrations' => Schema::hasTable('logistics_integrations')
                ? DB::table('logistics_integrations')->where('status', 'active')->orderBy('name')->get(['integration_id', 'name'])
                : collect(),
            'lots' => DB::table('lot')->orderBy('lot_name')->get(['lot_id', 'lot_name']),
            'locations' => DB::table('school')->whereNotNull('region')->where('region', '<>', '')->distinct()->orderBy('region')->pluck('region'),
            'years' => DB::table('deliveries')->whereNotNull('delivery_date')->selectRaw(DB::connection()->getDriverName() === 'sqlite' ? "CAST(strftime('%Y', delivery_date) AS INTEGER) as year" : 'YEAR(delivery_date) as year')->distinct()->orderByDesc('year')->pluck('year'),
        ]);
    }
}
