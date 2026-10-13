<?php

namespace App\Http\Controllers;

use App\Models\DeliveryBillingRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BillingDashboardController extends Controller
{
    public function index(): View
    {
        $totals = DB::table('deliveries as d')
            ->leftJoin('delivery_billing_records as billing', 'billing.delivery_id', '=', 'd.delivery_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN billing.delivery_id IS NULL OR billing.status = 'pending_billing' THEN 1 ELSE 0 END) as pending_billing")
            ->selectRaw("SUM(CASE WHEN billing.status = 'for_billing' THEN 1 ELSE 0 END) as for_billing")
            ->selectRaw("SUM(CASE WHEN billing.status = 'billed' THEN 1 ELSE 0 END) as billed")
            ->selectRaw("SUM(CASE WHEN billing.status = 'on_hold' THEN 1 ELSE 0 END) as on_hold")
            ->first();

        $totalDeliveries = (int) $totals->total;
        $billed = (int) $totals->billed;
        $completionPercentage = $totalDeliveries > 0
            ? round(($billed / $totalDeliveries) * 100, 1)
            : 0;

        $projects = DB::table('deliveries as d')
            ->join('projects as p', 'p.project_id', '=', 'd.project_id')
            ->leftJoin('delivery_billing_records as billing', 'billing.delivery_id', '=', 'd.delivery_id')
            ->groupBy('p.project_id', 'p.project_name')
            ->select('p.project_id', 'p.project_name')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN billing.status = 'for_billing' THEN 1 ELSE 0 END) as for_billing")
            ->selectRaw("SUM(CASE WHEN billing.status = 'billed' THEN 1 ELSE 0 END) as billed")
            ->selectRaw("SUM(CASE WHEN billing.status = 'on_hold' THEN 1 ELSE 0 END) as on_hold")
            ->orderBy('p.project_name')
            ->get();

        $byDate = DB::table('deliveries as d')
            ->leftJoin('delivery_billing_records as billing', 'billing.delivery_id', '=', 'd.delivery_id')
            ->groupBy('d.delivery_date')
            ->select('d.delivery_date')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN billing.status = 'billed' THEN 1 ELSE 0 END) as billed")
            ->orderByDesc('d.delivery_date')
            ->limit(14)
            ->get();

        $assignedPersonnel = DB::table('delivery_billing_records as billing')
            ->join('users as submitter', 'submitter.user_id', '=', 'billing.submitted_for_billing_by')
            ->groupBy('submitter.user_id', 'submitter.name')
            ->select('submitter.user_id', 'submitter.name')
            ->selectRaw('COUNT(*) as submitted_count')
            ->selectRaw("SUM(CASE WHEN billing.status = 'billed' THEN 1 ELSE 0 END) as billed_count")
            ->orderByDesc('submitted_count')
            ->limit(10)
            ->get();

        $recentActivity = DB::table('delivery_billing_histories as history')
            ->join('delivery_billing_records as billing', 'billing.id', '=', 'history.delivery_billing_record_id')
            ->join('deliveries as d', 'd.delivery_id', '=', 'billing.delivery_id')
            ->leftJoin('users as actor', 'actor.user_id', '=', 'history.changed_by')
            ->leftJoin('invoices as invoice', 'invoice.invoice_id', '=', 'history.invoice_id')
            ->select([
                'history.previous_status',
                'history.new_status',
                'history.remarks',
                'history.created_at',
                'actor.name as actor_name',
                'd.delivery_id',
                'd.dr_no',
                'invoice.invoice_no',
            ])
            ->orderByDesc('history.created_at')
            ->orderByDesc('history.id')
            ->limit(12)
            ->get();

        return view('billing.dashboard', [
            'totals' => [
                'total' => $totalDeliveries,
                'pending_billing' => (int) $totals->pending_billing,
                'for_billing' => (int) $totals->for_billing,
                'billed' => $billed,
                'on_hold' => (int) $totals->on_hold,
                'completion_percentage' => $completionPercentage,
            ],
            'projects' => $projects,
            'byDate' => $byDate,
            'assignedPersonnel' => $assignedPersonnel,
            'recentActivity' => $recentActivity,
            'statuses' => [
                DeliveryBillingRecord::PENDING_BILLING => 'Pending Billing',
                DeliveryBillingRecord::FOR_BILLING => 'For Billing',
                DeliveryBillingRecord::BILLED => 'Billed',
                DeliveryBillingRecord::ON_HOLD => 'On Hold',
            ],
        ]);
    }
}
