<?php

namespace App\Http\Controllers;

use App\Models\MI_Liquidation;
use App\Models\MI_LiquidationItem;
use App\Services\MiFinancialAmount;
use App\Services\MiFinancialWorkflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class LiquidationController extends Controller
{
    /**
     * Display liquidation reports.
     */
    public function downloadPdf(MI_Liquidation $liquidation): Response
    {
        Gate::authorize('view', $liquidation);
        $liquidation->load('items', 'preparer', 'company', 'activities');

        $pdf = Pdf::loadView('mi_app.liquidation.liquidation_pdf', compact('liquidation'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('Liquidation-'.str_pad($liquidation->id, 6, '0', STR_PAD_LEFT).'.pdf');
    }

    public function index(Request $request): View
    {
        $query = MI_Liquidation::query()->where('company_id', session('company_id'))->where('prepared_by', $request->user()->getKey())
            ->with([
                'items',
                'preparer',
                'company',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        |
        | Search by:
        | - Report title
        | - Liquidation ID
        | - Reference number
        | - Payee
        |
        */

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search) {

                $q->where(
                    'title',
                    'like',
                    "%{$search}%"
                );

                /*
                | Search report ID
                */
                if (is_numeric($search)) {
                    $q->orWhere(
                        'id',
                        (int) $search
                    );
                }

                /*
                | Search item information
                */
                $q->orWhereHas('items', function ($itemQuery) use ($search) {

                    $itemQuery
                        ->where(
                            'ref_no',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'payee',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'expense_type',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'account_buyer',
                            'like',
                            "%{$search}%"
                        );
                });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Reports
        |--------------------------------------------------------------------------
        */

        $reports = $query
            ->orderByDesc('date_prepared')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Dashboard Statistics
        |--------------------------------------------------------------------------
        */

        $pendingCount = MI_Liquidation::query()->where('company_id', session('company_id'))->where('prepared_by', $request->user()->getKey())
            ->where('status', 'Pending')
            ->count();

        $approvedCount = MI_Liquidation::query()->where('company_id', session('company_id'))->where('prepared_by', $request->user()->getKey())
            ->where('status', 'Approved')
            ->count();

        $totalVnd = MI_LiquidationItem::query()
            ->whereHas('report', fn ($query) => $query->where('company_id', session('company_id'))->where('prepared_by', $request->user()->getKey()))
            ->sum('amount_vnd');

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return view(
            'mi_app.liquidation.index',
            compact(
                'reports',
                'pendingCount',
                'approvedCount',
                'totalVnd'
            )
        );
    }

    /**
     * Show create liquidation form.
     */
    public function create(): View
    {
        Gate::authorize('create', MI_Liquidation::class);
        /*
        |--------------------------------------------------------------------------
        | Dropdown Options
        |--------------------------------------------------------------------------
        */

        $payeeOptions = collect();

        $accountBuyerOptions = collect();

        return view(
            'mi_app.liquidation.create',
            compact(
                'payeeOptions',
                'accountBuyerOptions'
            )
        );
    }

    /**
     * Store a new liquidation report.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', MI_Liquidation::class);
        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        if (! $user) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'You must be logged in to create a liquidation report.'
                );
        }

        $uploadedPaths = [];
        try {

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            | requested_by is intentionally NOT validated from the browser.
            | The server determines requested_by using the logged-in user's
            | user_id.
            |
            */

            $validated = $request->validate([

                'report_title' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'date_prepared' => [
                    'required',
                    'date',
                ],

                'exchange_rate' => [
                    'required',
                    'numeric',
                    'gt:0',
                    'max:99999999.9999',
                    'regex:/^\d+(\.\d{1,4})?$/',
                ],

                'pcf_amount' => [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:999999999999.99',
                    'regex:/^\d+(\.\d{1,2})?$/',
                ],

                'items' => [
                    'required',
                    'array',
                    'list',
                    'min:1',
                ],

                'items.*' => ['required', 'array'],

                'items.*.ref_no' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'items.*.item_date' => [
                    'required',
                    'date',
                ],
                'items.*.requested_by' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'items.*.payee' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'items.*.expense_type' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'items.*.account_buyer' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'items.*.amount_vnd' => [
                    'required',
                    'numeric',
                    'min:0.01',
                    'max:999999999999.99',
                    'regex:/^\d+(\.\d{1,2})?$/',
                ],

                'items.*.remarks' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],

                'items.*.receipt_image' => [
                    'nullable',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:10240',
                ],
            ]);
            MiFinancialAmount::sum(array_column($validated['items'], 'amount_vnd'), 'items', '999999999999.99');

            /*
            |--------------------------------------------------------------------------
            | Company
            |--------------------------------------------------------------------------
            */

            $companyId = session('company_id');

            if (method_exists($user, 'currentCompany')) {

                $company = $user->currentCompany();

                if ($company) {
                    $companyId = $company->company_id;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Create Liquidation
            |--------------------------------------------------------------------------
            */

            $liquidation = DB::transaction(
                function () use (
                    $validated,
                    $request,
                    $user,
                    $companyId,
                    &$uploadedPaths
                ) {

                    $liquidation = MI_Liquidation::create([

                        'title' => $validated['report_title'],

                        'date_prepared' => $validated['date_prepared'],

                        'exchange_rate' => $validated['exchange_rate'],

                        'pcf_amount' => $validated['pcf_amount'] ?? null,

                        'company_id' => $companyId,

                        /*
                        | Always use logged-in user.
                        */
                        'prepared_by' => $user->user_id,

                        'status' => 'Pending',
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Reference Date
                    |--------------------------------------------------------------------------
                    */

                    $datePrefix = Carbon::parse(
                        $validated['date_prepared']
                    )->format('Ymd');

                    /*
                    |--------------------------------------------------------------------------
                    | Create Items
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $validated['items'] as $index => $item
                    ) {

                        $receiptPath = null;

                        /*
                        |--------------------------------------------------------------------------
                        | Receipt Upload
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $request->hasFile(
                                "items.{$index}.receipt_image"
                            )
                        ) {

                            $file = $request->file(
                                "items.{$index}.receipt_image"
                            );

                            if (
                                $file &&
                                $file->isValid()
                            ) {

                                $receiptPath = $file->store('private/liquidations/receipts', 'local');
                                if (! $receiptPath) {
                                    throw new \RuntimeException('Unable to store receipt.');
                                }
                                $uploadedPaths[] = $receiptPath;
                            }
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Reference Number
                        |--------------------------------------------------------------------------
                        */

                        $refNo = sprintf(
                            'LF-%s-%03d',
                            $datePrefix,
                            $index + 1
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Create Item
                        |--------------------------------------------------------------------------
                        */

                        MI_LiquidationItem::create([

                            'liquidation_id' => $liquidation->id,

                            'ref_no' => $refNo,

                            'line_no' => $index + 1,

                            'item_date' => $item['item_date'],

                            'requested_by' => $item['requested_by'],

                            'payee' => $item['payee'],

                            'expense_type' => $item['expense_type'],

                            'account_buyer' => $item['account_buyer'],

                            'amount_vnd' => $item['amount_vnd'],

                            'remarks' => $item['remarks'] ?? null,

                            'receipt_image' => $receiptPath,
                        ]);
                    }

                    app(MiFinancialWorkflowService::class)->activity($liquidation, $user, 'ordinary_liquidation_created');

                    return $liquidation;
                }
            );

            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */

            return redirect()
                ->route(
                    'liquidation.show',
                    $liquidation->id
                )
                ->with(
                    'success',
                    'Liquidation report created successfully.'
                );

        } catch (ValidationException $e) {
            Storage::disk('local')->delete($uploadedPaths);
            throw $e;
        } catch (Throwable $e) {
            Storage::disk('local')->delete($uploadedPaths);

            Log::error(
                'Liquidation save failed',
                [
                    'user_id' => $user->user_id ?? null,

                    'message' => $e->getMessage(),

                    'file' => $e->getFile(),

                    'line' => $e->getLine(),

                    'trace' => $e->getTraceAsString(),
                ]
            );

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Unable to save the liquidation report. '.
                    'Please check the required fields and try again.'
                );
        }
    }

    /**
     * Display a single liquidation report.
     */
    public function show(
        MI_Liquidation $liquidation
    ): View {

        /*
        |--------------------------------------------------------------------------
        | Load Relationships
        |--------------------------------------------------------------------------
        */

        Gate::authorize('view', $liquidation);
        $liquidation->load([
            'activities',
            'items',
            'preparer',
            'company',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Return DETAIL View
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Do NOT return index.blade.php here.
        |
        */

        return view(
            'mi_app.liquidation.show',
            [
                'liquidation' => $liquidation,
            ]
        );
    }

    /**
     * Show edit liquidation form.
     */
    public function edit(MI_Liquidation $liquidation): View
    {
        Gate::authorize('update', $liquidation);
        $liquidation->load('items', 'preparer', 'company', 'activities');

        return view('mi_app.liquidation.edit', ['report' => $liquidation]);
    }

    /**
     * Update liquidation report.
     */
    public function update(Request $request, MI_Liquidation $liquidation): RedirectResponse
    {
        Gate::authorize('update', $liquidation);
        $validated = $request->validate([

            'report_title' => [
                'required',
                'string',
                'max:255',
            ],

            'date_prepared' => [
                'required',
                'date',
            ],

            'exchange_rate' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999.9999',
                'regex:/^\d+(\.\d{1,4})?$/',
            ],

            'pcf_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],

            'items' => [
                'required',
                'array',
                'list',
                'min:1',
            ],

            'items.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('mi_liquidation_items', 'id')->where('liquidation_id', $liquidation->getKey())],
            'items.*' => ['required', 'array'],

            'items.*.ref_no' => [
                'nullable',
                'string',
                'max:50',
            ],

            'items.*.item_date' => [
                'required',
                'date',
            ],

            'items.*.requested_by' => [
                'required',
                'string',
                'max:255',
            ],

            'items.*.payee' => [
                'required',
                'string',
                'max:255',
            ],

            'items.*.expense_type' => [
                'required',
                'string',
                'max:255',
            ],

            'items.*.account_buyer' => [
                'required',
                'string',
                'max:255',
            ],

            'items.*.amount_vnd' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],

            'items.*.remarks' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'items.*.receipt_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
            ],
        ]);

        MiFinancialAmount::sum(array_column($validated['items'], 'amount_vnd'), 'items', '999999999999.99');
        $uploadedPaths = [];
        try {
            DB::transaction(function () use ($validated, $request, $liquidation, &$uploadedPaths): void {
                $liquidation = MI_Liquidation::query()->whereKey($liquidation->getKey())->lockForUpdate()->firstOrFail();
                Gate::authorize('update', $liquidation);
                $liquidation->update([
                    'title' => $validated['report_title'],
                    'date_prepared' => $validated['date_prepared'],
                    'exchange_rate' => $validated['exchange_rate'],
                    'pcf_amount' => $validated['pcf_amount'] ?? null,
                ]);
                $keptIds = [];
                $datePrefix = Carbon::parse($validated['date_prepared'])->format('Ymd');
                foreach (array_values($validated['items']) as $index => $row) {
                    $item = isset($row['id'])
                        ? $liquidation->items()->whereKey($row['id'])->firstOrFail()
                        : $liquidation->items()->make();
                    $receiptPath = $item->receipt_image;
                    if ($request->hasFile("items.{$index}.receipt_image")) {
                        $receiptPath = $request->file("items.{$index}.receipt_image")->store('private/liquidations/receipts', 'local');
                        if (! $receiptPath) {
                            throw new \RuntimeException('Unable to store receipt.');
                        }
                        $uploadedPaths[] = $receiptPath;
                    }
                    $item->fill([
                        'line_no' => $index + 1,
                        'ref_no' => $item->ref_no ?? sprintf('LF-%s-%03d', $datePrefix, $index + 1),
                        'item_date' => $row['item_date'],
                        'requested_by' => $row['requested_by'],
                        'payee' => $row['payee'],
                        'expense_type' => $row['expense_type'],
                        'account_buyer' => $row['account_buyer'],
                        'amount_vnd' => $row['amount_vnd'],
                        'remarks' => $row['remarks'] ?? null,
                        'receipt_image' => $receiptPath,
                    ])->save();
                    $keptIds[] = $item->getKey();
                }
                $removed = $liquidation->items()->whereNotIn('id', $keptIds)->get()->toArray();
                app(MiFinancialWorkflowService::class)->activity($liquidation, $request->user(), 'ordinary_liquidation_updated', $liquidation->status, ['metadata' => ['removed_items' => $removed]]);
                $liquidation->items()->whereNotIn('id', $keptIds)->delete();
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($uploadedPaths);
            throw $exception;
        }

        return redirect()->route('liquidation.show', $liquidation)->with('success', 'Liquidation report updated successfully.');
    }

    /**
     * Delete liquidation report.
     */
    public function destroy(MI_Liquidation $liquidation): RedirectResponse
    {
        DB::transaction(function () use ($liquidation): void {
            $liquidation = MI_Liquidation::query()->whereKey($liquidation->getKey())->lockForUpdate()->firstOrFail();
            Gate::authorize('delete', $liquidation);
            app(MiFinancialWorkflowService::class)->activity($liquidation, auth()->user(), 'ordinary_liquidation_archived', $liquidation->status);
            $liquidation->delete();
        });

        return redirect()->route('liquidation.index')->with('success', 'Liquidation report deleted successfully.');
    }

    public function receipt(MI_LiquidationItem $item): StreamedResponse
    {
        abort_unless($item->report, 404);
        Gate::authorize('view', $item->report);
        $path = $item->receipt_image;
        abort_unless(is_string($path) && preg_match('~^(?:private/)?liquidations/receipts/[A-Za-z0-9._-]+$~D', $path), 404);
        $disk = str_starts_with($path, 'private/') ? 'local' : 'public';
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->response($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
