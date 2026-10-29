<?php

namespace App\Http\Controllers;

use App\Exceptions\BillingTransitionException;
use App\Models\BillingBatch;
use App\Models\BillingSheetSync;
use App\Models\DeliveryBillingRecord;
use App\Models\User;
use App\Services\BillingSheetSyncService;
use App\Services\DeliveryBillingService;
use App\Services\GoogleSheetsApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                    ->where('billing.status', '!=', DeliveryBillingRecord::REVOKED)
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
                'billing.id as billing_record_id', 'd.delivery_id', 'd.dr_no', 'p.project_name', 'l.lot_name', 's.school_name',
                'd.delivery_date', 'd.status as delivery_status', 'billing.status as billing_status',
                'invoice.invoice_no', 'billing.invoice_id',
            ]);
        $recordGroups = $records->groupBy('dr_no');
        $billingRecordIds = DB::table('delivery_billing_records')
            ->where('billing_batch_id', $batch->getKey())
            ->pluck('id');
        $historyIds = DB::table('delivery_billing_histories')
            ->whereIn('delivery_billing_record_id', $billingRecordIds)
            ->where('new_status', 'for_billing')
            ->orderByDesc('id')
            ->get(['id', 'delivery_billing_record_id'])
            ->unique('delivery_billing_record_id')
            ->pluck('id');
        $sheetSyncs = BillingSheetSync::query()
            ->whereIn('delivery_billing_history_id', $historyIds)
            ->orderBy('id')
            ->get();
        $revokedDeliveryIds = $records->where('billing_status', DeliveryBillingRecord::REVOKED)
            ->pluck('delivery_id')
            ->map(fn (mixed $deliveryId): int => (int) $deliveryId)
            ->all();
        $sheetSyncs->each(fn (BillingSheetSync $sync) => $sync->setAttribute(
            'billing_revoked',
            in_array((int) $sync->delivery_id, $revokedDeliveryIds, true)
        ));
        $activeSheetSyncs = $sheetSyncs->where('billing_revoked', false)->values();
        $revokedSheetSyncCount = $sheetSyncs->where('billing_revoked', true)->count();
        $activeBillingRecordCount = $records->where('billing_status', '!=', DeliveryBillingRecord::REVOKED)->count();
        $sheetSyncNeedsRetry = $activeBillingRecordCount === 0
            ? false
            : ($activeSheetSyncs->isEmpty()
            ? $revokedSheetSyncCount === 0
            : $activeSheetSyncs->contains(fn (BillingSheetSync $sync): bool => ! in_array($sync->outcome, ['synced', 'already_synced'], true)
                || (int) $sync->sync_version < 2));
        $activeDrCount = $records->where('billing_status', '!=', DeliveryBillingRecord::REVOKED)
            ->pluck('dr_no')->filter()->unique()->count();
        $revocationHistories = DB::table('delivery_billing_histories')
            ->whereIn('delivery_billing_record_id', $records->where('billing_status', DeliveryBillingRecord::REVOKED)->pluck('billing_record_id'))
            ->where('new_status', DeliveryBillingRecord::REVOKED)
            ->orderByDesc('id')
            ->get(['delivery_billing_record_id', 'changed_by_name', 'created_at', 'remarks'])
            ->unique('delivery_billing_record_id')
            ->keyBy('delivery_billing_record_id');

        return view('billing.batches.show', compact('batch', 'records', 'recordGroups', 'sheetSyncs', 'activeSheetSyncs', 'revokedSheetSyncCount', 'sheetSyncNeedsRetry', 'activeDrCount', 'activeBillingRecordCount', 'revocationHistories'));
    }

    public function revokeRecord(Request $request, BillingBatch $batch, DeliveryBillingRecord $record, DeliveryBillingService $billing): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        try {
            $billing->revokeFromBatch(
                (int) $record->getKey(),
                (int) $batch->getKey(),
                $actor,
                isset($validated['reason']) ? trim($validated['reason']) ?: null : null
            );
        } catch (BillingTransitionException $exception) {
            return redirect()->route('billing.batches.show', $batch)->with('error', $exception->getMessage());
        }

        return redirect()->route('billing.batches.show', $batch)
            ->with('success', 'The selected DR delivery and its package rows were revoked and excluded from future billing and Google Sheets syncs.');
    }

    public function retrySheetSync(BillingBatch $batch, BillingSheetSyncService $sheetSync, GoogleSheetsApiClient $sheets): RedirectResponse
    {
        $result = $sheetSync->syncBatch((int) $batch->getKey(), $sheets);

        return redirect()->route('billing.batches.show', $batch)
            ->with($result['status'] === 'success' ? 'success' : 'error', $result['message']);
    }
}
