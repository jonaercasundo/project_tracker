<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeliveryMonitoringDetailsRequest;
use App\Http\Requests\DeliveryMonitoringRequest;
use App\Services\ProjectDeliveryProgressService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DeliveryMonitoringController extends Controller
{
    public function __construct(private ProjectDeliveryProgressService $progress) {}

    public function details(DeliveryMonitoringDetailsRequest $request, int $project): JsonResponse
    {
        $filters = array_filter($request->validated(), fn (mixed $value): bool => $value !== null && $value !== '');
        $filters['project_id'] = $project;
        $section = $filters['section'];
        $records = $this->progress->detailRecords($filters, $section, (int) ($filters['per_page'] ?? 25), (int) ($filters['page'] ?? 1));

        return response()->json([
            'section' => $section,
            'records' => $records,
            'html' => view('operation.delivery.partials.monitoring-detail-records', compact('records', 'section'))->render(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function index(DeliveryMonitoringRequest $request): View|JsonResponse
    {
        $filters = array_filter($request->validated(), fn (mixed $value): bool => $value !== null && $value !== '');
        $report = $this->progress->report($filters);
        if ($request->expectsJson()) {
            return response()->json($report + [
                'summary_html' => view('operation.delivery.partials.monitoring-results', ['report' => $report])->render(),
                'projects_html' => view('operation.delivery.partials.monitoring-projects', ['report' => $report])->render(),
            ])->header('Cache-Control', 'private, no-store');
        }

        return view('operation.delivery.monitoring', [
            'report' => $report,
            'filters' => $filters,
            'projects' => DB::table('projects')->orderBy('project_name')->get(['project_id', 'project_name']),
            'locations' => DB::table('school')->select(['region', 'division', 'municipality'])->distinct()->orderBy('region')->orderBy('division')->orderBy('municipality')->get(),
            'years' => DB::table('deliveries')->whereNotNull('delivery_date')->selectRaw(DB::connection()->getDriverName() === 'sqlite' ? "CAST(strftime('%Y', delivery_date) AS INTEGER) as year" : 'YEAR(delivery_date) as year')->distinct()->orderByDesc('year')->pluck('year'),
        ]);
    }
}
