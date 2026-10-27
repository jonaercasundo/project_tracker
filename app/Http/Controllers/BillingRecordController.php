<?php

namespace App\Http\Controllers;

use App\Models\BillingSheetSync;
use App\Models\DeliveryBillingRecord;
use App\Services\BillingSheetSyncService;
use App\Services\GoogleSheetsApiClient;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BillingRecordController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $drGroups = $this->recordsQuery($filters)
            ->select('d.dr_no')
            ->selectRaw('MAX(d.delivery_date) as latest_delivery_date')
            ->groupBy('d.dr_no')
            ->orderByDesc('latest_delivery_date')
            ->orderBy('d.dr_no')
            ->paginate(25)
            ->withQueryString();
        $pageDrNumbers = collect($drGroups->items())->pluck('dr_no');
        $recordsQuery = $this->recordsQuery($filters);
        $nonNullDrNumbers = $pageDrNumbers->filter(fn (mixed $drNumber): bool => $drNumber !== null)->values();
        $includesNullDrNumber = $pageDrNumbers->contains(fn (mixed $drNumber): bool => $drNumber === null);

        if ($pageDrNumbers->isEmpty()) {
            $records = collect();
        } else {
            $records = $recordsQuery
                ->where(function (Builder $query) use ($nonNullDrNumbers, $includesNullDrNumber): void {
                    if ($nonNullDrNumbers->isNotEmpty()) {
                        $query->whereIn('d.dr_no', $nonNullDrNumbers->all());
                    }

                    if ($includesNullDrNumber) {
                        $nonNullDrNumbers->isNotEmpty()
                            ? $query->orWhereNull('d.dr_no')
                            : $query->whereNull('d.dr_no');
                    }
                })
                ->orderBy('d.dr_no')
                ->orderByDesc('d.delivery_date')
                ->orderByDesc('d.delivery_id')
                ->orderByDesc('billing.id')
                ->get()
                ->groupBy('dr_no');
        }

        return view('billing.records.index', [
            'drGroups' => $drGroups,
            'recordGroups' => $records,
            'duplicateLedgerGroups' => $this->duplicateLedgerGroups($filters),
            'filters' => $filters,
            'projects' => DB::table('projects')->orderBy('project_name')->get(['project_id', 'project_name', 'project_code', 'ref_no']),
            'schools' => DB::table('school')->orderBy('school_name')->get(['school_id', 'school_name']),
            'statuses' => [
                DeliveryBillingRecord::PENDING_BILLING => 'Pending Billing',
                DeliveryBillingRecord::FOR_BILLING => 'For Billing',
                DeliveryBillingRecord::BILLED => 'Billed',
                DeliveryBillingRecord::ON_HOLD => 'On Hold',
            ],
        ]);
    }

    public function show(int $delivery): View
    {
        $record = $this->recordsQuery([])
            ->where('d.delivery_id', $delivery)
            ->firstOrFail();

        $history = DB::table('delivery_billing_histories as history')
            ->join('delivery_billing_records as billing', 'billing.id', '=', 'history.delivery_billing_record_id')
            ->leftJoin('users as actor', 'actor.user_id', '=', 'history.changed_by')
            ->leftJoin('invoices as invoice', 'invoice.invoice_id', '=', 'history.invoice_id')
            ->where('billing.delivery_id', $delivery)
            ->orderByDesc('history.created_at')
            ->orderByDesc('history.id')
            ->get([
                'history.previous_status',
                'history.new_status',
                'history.remarks',
                'history.created_at',
                DB::raw('COALESCE(history.changed_by_name, actor.name) as actor_name'),
                'invoice.invoice_no',
                'invoice.invoice_date',
                'invoice.amount as invoice_amount',
            ]);

        $sheetSyncs = BillingSheetSync::query()
            ->where('delivery_id', $delivery)
            ->orderByDesc('id')
            ->get();

        return view('billing.records.show', [
            'record' => $record,
            'history' => $history,
            'sheetSyncs' => $sheetSyncs,
            'sheetConfiguration' => app(GoogleSheetsApiClient::class)->configurationStatus(),
        ]);
    }

    public function retrySheetSync(int $delivery, BillingSheetSync $sync, BillingSheetSyncService $syncService, GoogleSheetsApiClient $sheets): RedirectResponse
    {
        abort_unless((int) $sync->delivery_id === $delivery, 404);
        abort_if(in_array($sync->outcome, ['synced', 'already_synced', 'not_applicable'], true), 409);

        $billingBatchId = DeliveryBillingRecord::query()
            ->where('delivery_id', $delivery)
            ->value('billing_batch_id');
        if ($billingBatchId !== null) {
            $result = $syncService->syncBatch((int) $billingBatchId, $sheets);

            return redirect()->route('billing.records.show', $delivery)
                ->with($result['status'] === 'success' ? 'success' : 'error', $result['message']);
        }

        $sync->forceFill(['outcome' => 'queued', 'error_details' => null])->save();
        $syncService->dispatch($sync);

        return redirect()->route('billing.records.show', $delivery)->with('success', 'Google Sheets reconciliation has been queued.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $query = $this->recordsQuery($filters)->orderBy('d.delivery_id');

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                throw new \RuntimeException('Unable to open the CSV export stream.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'DR Number',
                'Project',
                'School',
                'Delivery Date',
                'Delivery Status',
                'Billing Status',
                'Invoice Number',
                'Invoice Date',
                'Invoice Amount',
                'Delivery Billing Amount',
                'Billing Reference',
                'Billing Remarks',
            ]);

            $query->chunk(500, function ($rows) use ($output): void {
                foreach ($rows as $record) {
                    fputcsv($output, [
                        $this->safeCsvValue($record->dr_no),
                        $this->safeCsvValue($record->project_name),
                        $this->safeCsvValue($record->school_name),
                        $record->delivery_date,
                        $record->delivery_status,
                        $record->billing_status,
                        $this->safeCsvValue($record->invoice_no),
                        $record->invoice_date,
                        $record->invoice_amount,
                        $record->billing_amount,
                        $this->safeCsvValue($record->billing_reference),
                        $this->safeCsvValue($record->billing_remarks),
                    ]);
                }
            });

            fclose($output);
        }, 'billing-records.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function recordsQuery(array $filters, bool $applyDistinct = true): Builder
    {
        return DB::table('deliveries as d')
            ->leftJoin('projects as p', 'p.project_id', '=', 'd.project_id')
            ->leftJoin('lot as l', 'l.lot_id', '=', 'd.lot_id')
            ->leftJoin('school as s', 's.school_id', '=', 'd.school_id')
            ->leftJoin('delivery_billing_records as billing', 'billing.delivery_id', '=', 'd.delivery_id')
            ->leftJoin('invoices as invoice', 'invoice.invoice_id', '=', 'billing.invoice_id')
            ->leftJoin('billing_batches as batch', 'batch.id', '=', 'billing.billing_batch_id')
            ->select([
                'billing.id as billing_record_id',
                'd.delivery_id',
                'd.dr_no',
                'd.project_id',
                'p.project_name',
                'l.lot_name',
                'd.school_id',
                's.school_name',
                'd.delivery_date',
                'd.status as delivery_status',
                'invoice.invoice_no',
                'invoice.invoice_date',
                'invoice.amount as invoice_amount',
                'batch.id as billing_batch_id',
                'batch.batch_ref',
                'invoice.status as invoice_status',
                'billing.billing_amount',
                'billing.billing_reference',
                'billing.billing_remarks',
            ])
            ->when($applyDistinct, fn (Builder $query) => $query->distinct())
            ->selectRaw("COALESCE(billing.status, 'pending_billing') as billing_status")
            ->when(isset($filters['project_id']), fn (Builder $query) => $query->where('d.project_id', $filters['project_id']))
            ->when(isset($filters['dr_no']), fn (Builder $query) => $query->where('d.dr_no', 'like', '%'.$filters['dr_no'].'%'))
            ->when(isset($filters['school_id']), fn (Builder $query) => $query->where('d.school_id', $filters['school_id']))
            ->when(isset($filters['billing_status']), function (Builder $query) use ($filters): void {
                if ($filters['billing_status'] === DeliveryBillingRecord::PENDING_BILLING) {
                    $query->where(function (Builder $pending): void {
                        $pending->whereNull('billing.delivery_id')
                            ->orWhere('billing.status', DeliveryBillingRecord::PENDING_BILLING);
                    });
                } else {
                    $query->where('billing.status', $filters['billing_status']);
                }
            })
            ->when(isset($filters['date_from']), fn (Builder $query) => $query->whereDate('d.delivery_date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn (Builder $query) => $query->whereDate('d.delivery_date', '<=', $filters['date_to']))
            ->when(isset($filters['invoice_no']), fn (Builder $query) => $query->where('invoice.invoice_no', 'like', '%'.$filters['invoice_no'].'%'));
    }

    /** @param array<string, mixed> $filters
     * @return Collection<int, object>
     */
    private function duplicateLedgerGroups(array $filters): Collection
    {
        $groups = $this->recordsQuery($filters, applyDistinct: false)
            ->whereNotNull('billing.id')
            ->whereNotNull('d.dr_no')
            ->whereRaw("TRIM(d.dr_no) <> ''")
            ->select('d.project_id', 'd.school_id', 'd.lot_id', 'd.delivery_date', 'billing.billing_batch_id')
            ->selectRaw('LOWER(TRIM(d.dr_no)) as normalized_dr, COUNT(billing.id) as billing_record_count')
            ->groupBy('d.project_id', 'd.school_id', 'd.lot_id', 'd.delivery_date', 'billing.billing_batch_id')
            ->groupByRaw('LOWER(TRIM(d.dr_no))')
            ->havingRaw('COUNT(billing.id) > 1')
            ->get();

        return $groups->map(function (object $group) use ($filters): object {
            $recordsQuery = $this->recordsQuery($filters, applyDistinct: false)
                ->where('d.project_id', $group->project_id)
                ->where('d.school_id', $group->school_id)
                ->where('d.delivery_date', $group->delivery_date)
                ->whereRaw('LOWER(TRIM(d.dr_no)) = ?', [$group->normalized_dr]);
            $group->lot_id === null
                ? $recordsQuery->whereNull('d.lot_id')
                : $recordsQuery->where('d.lot_id', $group->lot_id);
            $group->billing_batch_id === null
                ? $recordsQuery->whereNull('billing.billing_batch_id')
                : $recordsQuery->where('billing.billing_batch_id', $group->billing_batch_id);

            $duplicates = $recordsQuery->get(['billing.id as billing_record_id', 'd.delivery_id']);
            $group->ledger_row_count = $duplicates->count();
            $group->billing_record_ids = $duplicates->pluck('billing_record_id')->unique()->values()->all();
            $group->delivery_ids = $duplicates->pluck('delivery_id')->unique()->values()->all();
            $group->duplicate_source = count($group->billing_record_ids) < $group->ledger_row_count
                ? 'One or more joins multiplied a billing record in the ledger query.'
                : 'Multiple billing records exist in the stored ledger for this project, DR, school, date, lot, and batch scope; a separate reviewed cleanup is required.';
            $group->dr_no = $this->recordsQuery($filters, applyDistinct: false)
                ->where('d.project_id', $group->project_id)
                ->where('d.school_id', $group->school_id)
                ->where('d.delivery_date', $group->delivery_date)
                ->whereRaw('LOWER(TRIM(d.dr_no)) = ?', [$group->normalized_dr])
                ->value('d.dr_no');
            $group->project_name = DB::table('projects')->where('project_id', $group->project_id)->value('project_name');
            $group->school_name = DB::table('school')->where('school_id', $group->school_id)->value('school_name');
            $group->lot_name = $group->lot_id === null ? null : DB::table('lot')->where('lot_id', $group->lot_id)->value('lot_name');
            $group->batch_ref = $group->billing_batch_id === null ? null : DB::table('billing_batches')->where('id', $group->billing_batch_id)->value('batch_ref');

            return $group;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'project_id' => ['nullable', 'integer', 'exists:projects,project_id'],
            'dr_no' => ['nullable', 'string', 'max:100'],
            'school_id' => ['nullable', 'string', 'max:255', 'exists:school,school_id'],
            'billing_status' => ['nullable', 'in:pending_billing,for_billing,billed,on_hold'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'invoice_no' => ['nullable', 'string', 'max:100'],
        ]);

        return array_filter($validated, static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    private function safeCsvValue(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^\s*[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }
}
