<?php

namespace App\Http\Controllers;

use App\Models\DeliveryBillingRecord;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BillingRecordController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $records = $this->recordsQuery($filters)
            ->orderByDesc('d.delivery_date')
            ->orderByDesc('d.delivery_id')
            ->paginate(25)
            ->withQueryString();

        return view('billing.records.index', [
            'records' => $records,
            'filters' => $filters,
            'projects' => DB::table('projects')->orderBy('project_name')->get(['project_id', 'project_name']),
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

        return view('billing.records.show', compact('record', 'history'));
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
    private function recordsQuery(array $filters): Builder
    {
        return DB::table('deliveries as d')
            ->leftJoin('projects as p', 'p.project_id', '=', 'd.project_id')
            ->leftJoin('school as s', 's.school_id', '=', 'd.school_id')
            ->leftJoin('delivery_billing_records as billing', 'billing.delivery_id', '=', 'd.delivery_id')
            ->leftJoin('invoices as invoice', 'invoice.invoice_id', '=', 'billing.invoice_id')
            ->select([
                'd.delivery_id',
                'd.dr_no',
                'd.project_id',
                'p.project_name',
                'd.school_id',
                's.school_name',
                'd.delivery_date',
                'd.status as delivery_status',
                'invoice.invoice_no',
                'invoice.invoice_date',
                'invoice.amount as invoice_amount',
                'invoice.status as invoice_status',
                'billing.billing_amount',
                'billing.billing_reference',
                'billing.billing_remarks',
            ])
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
