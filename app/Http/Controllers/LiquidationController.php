<?php

namespace App\Http\Controllers;

use App\Models\AiAssistantAttachment;
use App\Models\MI_Liquidation;
use App\Models\MI_LiquidationItem;
use App\Models\MI_LiquidationItemReceipt;
use App\Services\MiFinancialAmount;
use App\Services\MiFinancialWorkflowService;
use App\Services\MiForexRateService;
use App\Services\MiLiquidationExpenseReferenceGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
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
    public function create(Request $request): View
    {
        Gate::authorize('create', MI_Liquidation::class);
        $user = $request->user();
        $company = $user?->currentCompany();
        $draftData = null;
        $draftId = $request->session()->get('ai_liquidation_draft_id');
        if ($draftId !== null && $company !== null) {
            $attachment = AiAssistantAttachment::query()->whereKey($draftId)
                ->where('user_id', $user->getKey())->where('company_id', $company->getKey())
                ->where('expires_at', '>', now())->first();
            if ($attachment !== null && $company->code === 'MI') {
                $draftData = $attachment->parsed_data;
            } else {
                $request->session()->forget('ai_liquidation_draft_id');
            }
        }
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
                'accountBuyerOptions',
                'draftData'
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

                'date_prepared' => [
                    'required',
                    'date_format:Y-m-d',
                ],

                'submission_key' => ['nullable', 'uuid'],

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

                'items.*.ocr_reference' => [
                    'nullable',
                    'string',
                    'max:255',
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

                'items.*.receipt_files' => ['sometimes', 'array', 'max:10'],
                'items.*.receipt_files.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'extensions:pdf,jpg,jpeg,png,webp', 'max:20480'],
                'items.*.receipt_ocr_references' => ['sometimes', 'array', 'max:10'],
                'items.*.receipt_ocr_references.*' => ['nullable', 'string', 'max:500'],
                'items.*.receipt_image' => [
                    'nullable',
                    'file',
                    'mimes:pdf,jpg,jpeg,png,webp',
                    'extensions:pdf,jpg,jpeg,png,webp',
                    'max:20480',
                ],
            ]);
            if ($this->receiptUploadBytes($request) > 35 * 1024 * 1024) {
                throw ValidationException::withMessages(['items' => 'Receipt uploads must total no more than 35 MB per submission.']);
            }
            MiFinancialAmount::sum(array_column($validated['items'], 'amount_vnd'), 'items', '999999999999.99');

            $companyId = session('company_id');
            if (method_exists($user, 'currentCompany')) {
                $companyId = $user->currentCompany()?->getKey() ?? $companyId;
            }
            $submissionKey = $validated['submission_key'] ?? (string) Str::uuid();
            $existing = MI_Liquidation::query()
                ->where('submission_key', $submissionKey)
                ->where('prepared_by', $user->getKey())
                ->where('company_id', $companyId)
                ->first();
            if ($existing) {
                $request->session()->forget('ai_liquidation_draft_id');

                return redirect()->route('liquidation.show', $existing)
                    ->with('success', 'This liquidation report was already saved.');
            }

            $rate = app(MiForexRateService::class)->currentRate();

            /*
            |--------------------------------------------------------------------------
            | Company
            |--------------------------------------------------------------------------
            */

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
                    $submissionKey,
                    $rate,
                    &$uploadedPaths
                ) {

                    $liquidation = MI_Liquidation::create([

                        'title' => Carbon::parse($validated['date_prepared'])->format('F Y').' Report',

                        'date_prepared' => $validated['date_prepared'],

                        'exchange_rate' => $rate['rate'],
                        'exchange_rate_source' => $rate['source'],
                        'submission_key' => $submissionKey,

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

                    /*
                    |--------------------------------------------------------------------------
                    | Create Items
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $validated['items'] as $index => $item
                    ) {

                        /*
                        |--------------------------------------------------------------------------
                        | Reference Number
                        |--------------------------------------------------------------------------
                        */

                        $refNo = app(MiLiquidationExpenseReferenceGenerator::class)
                            ->next($validated['date_prepared']);

                        $legacyReceipt = $request->file("items.{$index}.receipt_image");
                        $legacyReceiptPath = $legacyReceipt?->store('private/liquidations/receipts', 'local');
                        if (is_string($legacyReceiptPath)) {
                            $uploadedPaths[] = $legacyReceiptPath;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Create Item
                        |--------------------------------------------------------------------------
                        */

                        $expense = MI_LiquidationItem::create([

                            'liquidation_id' => $liquidation->id,

                            'ref_no' => $refNo,

                            'ocr_reference' => $item['ocr_reference'] ?? null,

                            'line_no' => $index + 1,

                            'item_date' => $item['item_date'],

                            'requested_by' => $item['requested_by'],

                            'payee' => $item['payee'],

                            'expense_type' => $item['expense_type'],

                            'account_buyer' => $item['account_buyer'],

                            'amount_vnd' => $item['amount_vnd'],

                            'remarks' => $item['remarks'] ?? null,

                            'receipt_image' => $legacyReceiptPath,
                        ]);

                        $this->storeReceiptAttachments(
                            $expense,
                            $request->file("items.{$index}.receipt_files", []),
                            null,
                            $item['receipt_ocr_references'] ?? [],
                            (int) $user->getKey(),
                            $uploadedPaths
                        );
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

            $request->session()->forget('ai_liquidation_draft_id');

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
            $this->deleteStoredReceiptPaths($uploadedPaths);
            throw $e;
        } catch (Throwable $e) {
            $this->deleteStoredReceiptPaths($uploadedPaths);

            $duplicate = isset($submissionKey, $companyId)
                ? MI_Liquidation::query()->where('submission_key', $submissionKey)
                    ->where('prepared_by', $user->getKey())->where('company_id', $companyId)->first()
                : null;
            if ($duplicate) {
                return redirect()->route('liquidation.show', $duplicate)
                    ->with('success', 'This liquidation report was already saved.');
            }

            Log::error('Liquidation save failed.', ['user_id' => $user->getKey(), 'exception' => $e]);

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
            'items.receipts',
            'preparer',
            'company',
        ]);
        $this->prepareLegacyReceiptMetadata($liquidation);

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
        $liquidation->load('items.receipts', 'preparer', 'company', 'activities');
        $this->prepareLegacyReceiptMetadata($liquidation);

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
                'nullable',
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

            'items.*.ocr_reference' => [
                'nullable',
                'string',
                'max:255',
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

            'items.*.receipt_files' => ['sometimes', 'array', 'max:10'],
            'items.*.receipt_files.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'extensions:pdf,jpg,jpeg,png,webp', 'max:20480'],
            'items.*.receipt_ocr_references' => ['sometimes', 'array', 'max:10'],
            'items.*.receipt_ocr_references.*' => ['nullable', 'string', 'max:500'],
            'remove_receipt_ids' => ['sometimes', 'array', 'max:100'],
            'remove_receipt_ids.*' => ['required', 'integer', 'distinct'],
            'remove_legacy_receipt_ids' => ['sometimes', 'array', 'max:100'],
            'remove_legacy_receipt_ids.*' => ['required', 'integer', 'distinct'],
            'items.*.receipt_image' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'extensions:pdf,jpg,jpeg,png,webp',
                'max:20480',
            ],
        ]);

        if ($this->receiptUploadBytes($request) > 35 * 1024 * 1024) {
            throw ValidationException::withMessages(['items' => 'Receipt uploads must total no more than 35 MB per submission.']);
        }
        MiFinancialAmount::sum(array_column($validated['items'], 'amount_vnd'), 'items', '999999999999.99');
        $uploadedPaths = [];
        $receiptPathsToDelete = [];
        try {
            $rate = app(MiForexRateService::class)->currentRate();
            DB::transaction(function () use ($validated, $request, $liquidation, $rate, &$uploadedPaths, &$receiptPathsToDelete): void {
                $liquidation = MI_Liquidation::query()->whereKey($liquidation->getKey())->lockForUpdate()->firstOrFail();
                Gate::authorize('update', $liquidation);

                $removeReceiptIds = array_map('intval', $validated['remove_receipt_ids'] ?? []);
                $receiptsToRemove = MI_LiquidationItemReceipt::query()
                    ->whereIn('id', $removeReceiptIds)
                    ->whereHas('expense', fn ($query) => $query->where('liquidation_id', $liquidation->getKey()))
                    ->lockForUpdate()
                    ->get();
                if ($receiptsToRemove->count() !== count($removeReceiptIds)) {
                    throw ValidationException::withMessages(['remove_receipt_ids' => 'One or more selected receipts are not attached to this liquidation.']);
                }
                $receiptPathsToDelete = array_merge($receiptPathsToDelete, $receiptsToRemove->pluck('storage_path')->all());
                $receiptsToRemove->each->delete();

                $removeLegacyReceiptIds = array_map('intval', $validated['remove_legacy_receipt_ids'] ?? []);
                $legacyReceiptsToRemove = $liquidation->items()
                    ->whereIn('id', $removeLegacyReceiptIds)
                    ->whereNotNull('receipt_image')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');
                if ($legacyReceiptsToRemove->count() !== count($removeLegacyReceiptIds)) {
                    throw ValidationException::withMessages(['remove_legacy_receipt_ids' => 'One or more selected receipts are no longer available.']);
                }
                foreach ($legacyReceiptsToRemove as $legacyReceipt) {
                    $receiptPathsToDelete[] = $legacyReceipt->receipt_image;
                }

                $liquidation->update([
                    'title' => $validated['report_title'],
                    'date_prepared' => $validated['date_prepared'],
                    'exchange_rate' => $rate['rate'],
                    'exchange_rate_source' => $rate['source'],
                    'pcf_amount' => $validated['pcf_amount'] ?? null,
                ]);
                $keptIds = [];
                foreach (array_values($validated['items']) as $index => $row) {
                    $item = isset($row['id'])
                        ? $liquidation->items()->whereKey($row['id'])->firstOrFail()
                        : $liquidation->items()->make();
                    $receiptPath = $item->receipt_image;
                    if (in_array((int) $item->getKey(), $removeLegacyReceiptIds, true)) {
                        $receiptPath = null;
                    }
                    $item->fill([
                        'line_no' => $index + 1,
                        'ref_no' => $item->ref_no ?? app(MiLiquidationExpenseReferenceGenerator::class)
                            ->next($validated['date_prepared']),
                        'ocr_reference' => $row['ocr_reference'] ?? null,
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

                    $this->storeReceiptAttachments(
                        $item,
                        $request->file("items.{$index}.receipt_files", []),
                        $request->file("items.{$index}.receipt_image"),
                        $row['receipt_ocr_references'] ?? [],
                        (int) $request->user()->getKey(),
                        $uploadedPaths
                    );
                }
                $removedItems = $liquidation->items()->with('receipts')->whereNotIn('id', $keptIds)->get();
                foreach ($removedItems as $removedItem) {
                    if ($removedItem->receipt_image !== null) {
                        $receiptPathsToDelete[] = $removedItem->receipt_image;
                    }
                    $receiptPathsToDelete = array_merge($receiptPathsToDelete, $removedItem->receipts->pluck('storage_path')->all());
                }
                $removed = $removedItems->toArray();
                app(MiFinancialWorkflowService::class)->activity($liquidation, $request->user(), 'ordinary_liquidation_updated', $liquidation->status, ['metadata' => ['removed_items' => $removed]]);
                $liquidation->items()->whereNotIn('id', $keptIds)->delete();
            });
        } catch (Throwable $exception) {
            $this->deleteStoredReceiptPaths($uploadedPaths);
            throw $exception;
        }

        $this->deleteStoredReceiptPaths($receiptPathsToDelete);

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
        abort_unless(is_string($path) && ($disk = $this->legacyReceiptDisk($path)) !== null, 404);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return $this->streamReceipt($disk, $path, basename(str_replace('\\', '/', $path)), Storage::disk($disk)->mimeType($path), false);
    }

    public function downloadReceipt(MI_LiquidationItem $item): StreamedResponse
    {
        abort_unless($item->report, 404);
        Gate::authorize('view', $item->report);
        $path = $item->receipt_image;
        abort_unless(is_string($path) && ($disk = $this->legacyReceiptDisk($path)) !== null, 404);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return $this->streamReceipt($disk, $path, basename(str_replace('\\', '/', $path)), Storage::disk($disk)->mimeType($path), true);
    }

    public function receiptAttachment(MI_LiquidationItemReceipt $receipt): StreamedResponse
    {
        $receipt->loadMissing('expense.report');
        abort_unless($receipt->expense?->report, 404);
        Gate::authorize('view', $receipt->expense->report);

        $path = $receipt->storage_path;
        abort_unless(is_string($path) && preg_match('~^private/liquidations/receipts/[A-Za-z0-9._-]+$~D', $path), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return $this->streamReceipt('local', $path, $receipt->original_filename, $receipt->mime_type, false);
    }

    public function downloadReceiptAttachment(MI_LiquidationItemReceipt $receipt): StreamedResponse
    {
        $receipt->loadMissing('expense.report');
        abort_unless($receipt->expense?->report, 404);
        Gate::authorize('view', $receipt->expense->report);

        $path = $receipt->storage_path;
        abort_unless(is_string($path) && preg_match('~^private/liquidations/receipts/[A-Za-z0-9._-]+$~D', $path), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return $this->streamReceipt('local', $path, $receipt->original_filename, $receipt->mime_type, true);
    }

    /**
     * Save uploaded receipt files as private attachment records.
     *
     * @param  array<array-key, UploadedFile>|null  $receiptFiles
     * @param  array<array-key, string|null>  $ocrReferences
     * @param  array<int, string>  $uploadedPaths
     */
    private function storeReceiptAttachments(
        MI_LiquidationItem $expense,
        ?array $receiptFiles,
        ?UploadedFile $legacyUpload,
        array $ocrReferences,
        int $uploadedBy,
        array &$uploadedPaths
    ): void {
        $receiptFiles ??= [];

        if ($legacyUpload !== null) {
            $receiptFiles['legacy_'.count($receiptFiles)] = $legacyUpload;
        }

        if (count($receiptFiles) > 10) {
            throw ValidationException::withMessages(['items' => 'Upload no more than 10 receipts per expense.']);
        }

        foreach ($receiptFiles as $key => $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                throw ValidationException::withMessages(['items' => 'A receipt upload could not be read. Please select the file again.']);
            }

            $storagePath = $file->store('private/liquidations/receipts', 'local');
            if (! is_string($storagePath) || ! preg_match('~^private/liquidations/receipts/[A-Za-z0-9._-]+$~D', $storagePath)) {
                throw new \RuntimeException('Unable to store receipt.');
            }
            $uploadedPaths[] = $storagePath;

            $originalFilename = basename(str_replace('\\', '/', $file->getClientOriginalName()));
            $originalFilename = preg_replace('/[\x00-\x1F\x7F]/u', '_', $originalFilename) ?: 'receipt';

            MI_LiquidationItemReceipt::query()->create([
                'liquidation_item_id' => $expense->getKey(),
                'original_filename' => mb_substr($originalFilename, 0, 255),
                'storage_path' => $storagePath,
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'file_size' => (int) $file->getSize(),
                'uploaded_by' => $uploadedBy,
                'ocr_reference' => is_string($ocrReferences[$key] ?? null) ? trim($ocrReferences[$key]) : null,
            ]);
        }
    }

    private function receiptUploadBytes(Request $request): int
    {
        $totalBytes = 0;
        foreach ($request->file('items', []) as $rowFiles) {
            if (! is_array($rowFiles)) {
                continue;
            }

            foreach (['receipt_files', 'receipt_image'] as $field) {
                $files = $rowFiles[$field] ?? [];
                foreach (is_array($files) ? $files : [$files] as $file) {
                    if ($file instanceof UploadedFile && $file->isValid()) {
                        $totalBytes += (int) $file->getSize();
                    }
                }
            }
        }

        return $totalBytes;
    }

    /**
     * Delete generated receipt paths after their database rows have committed.
     *
     * @param  array<int, string>  $paths
     */
    private function deleteStoredReceiptPaths(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            if (! is_string($path) || ($disk = $this->legacyReceiptDisk($path)) === null) {
                continue;
            }

            try {
                if (Storage::disk($disk)->exists($path) && ! Storage::disk($disk)->delete($path)) {
                    Log::warning('Unable to delete removed liquidation receipt.', ['storage_path' => $path]);
                }
            } catch (Throwable $exception) {
                Log::warning('Unable to delete removed liquidation receipt.', ['storage_path' => $path, 'exception' => $exception]);
            }
        }
    }

    private function legacyReceiptDisk(string $path): ?string
    {
        if (preg_match('~^private/liquidations/receipts/[A-Za-z0-9._-]+$~D', $path) === 1) {
            return 'local';
        }

        if (preg_match('~^liquidations/receipts/[A-Za-z0-9._-]+$~D', $path) === 1) {
            return 'public';
        }

        return null;
    }

    private function streamReceipt(string $disk, string $path, string $filename, ?string $mimeType, bool $download): StreamedResponse
    {
        $stream = Storage::disk($disk)->readStream($path);
        abort_unless(is_resource($stream), 404);

        $safeMimeType = in_array($mimeType, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true)
            ? $mimeType
            : 'application/octet-stream';
        $filename = basename(str_replace('\\', '/', $filename));
        $filename = preg_replace('/[\x00-\x1F\x7F]/u', '_', $filename) ?: 'receipt';
        $filenameFallback = Str::ascii($filename);
        $filenameFallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $filenameFallback) ?: 'receipt';
        $disposition = (new ResponseHeaderBag)->makeDisposition($download ? 'attachment' : 'inline', $filename, $filenameFallback);

        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $safeMimeType,
            'Content-Disposition' => $disposition,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function prepareLegacyReceiptMetadata(MI_Liquidation $liquidation): void
    {
        foreach ($liquidation->items as $item) {
            $path = $item->receipt_image;
            $disk = is_string($path) ? $this->legacyReceiptDisk($path) : null;
            if ($disk === null) {
                continue;
            }

            try {
                if (! Storage::disk($disk)->exists($path)) {
                    continue;
                }

                $item->setAttribute('legacy_receipt', [
                    'filename' => basename(str_replace('\\', '/', $path)),
                    'file_size' => Storage::disk($disk)->size($path),
                    'mime_type' => Storage::disk($disk)->mimeType($path),
                ]);
            } catch (Throwable $exception) {
                Log::warning('Unable to read legacy liquidation receipt metadata.', ['storage_path' => $path, 'exception' => $exception]);
            }
        }
    }
}
