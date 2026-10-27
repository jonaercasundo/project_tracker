<?php

namespace App\Http\Controllers;

use App\Models\BillingBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BillingBatchController extends Controller
{
    public function index(): View
    {
        $batches = BillingBatch::query()
            ->with(['createdBy', 'confirmedBy'])
            ->addSelect([
                'dr_count' => DB::table('delivery_billing_records as billing')
                    ->join('deliveries as d', 'd.delivery_id', '=', 'billing.delivery_id')
                    ->whereColumn('billing.billing_batch_id', 'billing_batches.id')
                    ->selectRaw('COUNT(DISTINCT d.dr_no)'),
            ])
            ->orderByDesc('confirmed_at')
            ->orderByDesc('id')
            ->paginate(25);

        return view('billing.batches.index', compact('batches'));
    }

    public function show(BillingBatch $batch): View
    {
        $batch->load(['createdBy', 'confirmedBy']);
        $records = DB::table('delivery_billing_records as billing')
            ->join('deliveries as d', 'd.delivery_id', '=', 'billing.delivery_id')
            ->leftJoin('projects as p', 'p.project_id', '=', 'd.project_id')
            ->leftJoin('lot as l', 'l.lot_id', '=', 'd.lot_id')
            ->leftJoin('school as s', 's.school_id', '=', 'd.school_id')
            ->leftJoin('invoices as invoice', 'invoice.invoice_id', '=', 'billing.invoice_id')
            ->where('billing.billing_batch_id', $batch->getKey())
            ->orderBy('d.delivery_id')
            ->get([
                'd.delivery_id', 'd.dr_no', 'p.project_name', 'l.lot_name', 's.school_name',
                'd.delivery_date', 'd.status as delivery_status', 'billing.status as billing_status',
                'invoice.invoice_no',
            ]);
        $recordGroups = $records->groupBy('dr_no');

        return view('billing.batches.show', compact('batch', 'records', 'recordGroups'));
    }
}
