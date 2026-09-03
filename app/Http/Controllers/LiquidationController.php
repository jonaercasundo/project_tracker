<?php

namespace App\Http\Controllers;

use App\Models\MI_Liquidation;
use App\Models\MI_LiquidationItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class LiquidationController extends Controller
{
    /**
     * Display liquidation reports.
     */
    public function index(Request $request): View
    {
        $query = MI_Liquidation::query()
            ->with([
                'items',
                'items.requestedBy',
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

        $pendingCount = MI_Liquidation::query()
            ->where('status', 'Pending')
            ->count();

        $approvedCount = MI_Liquidation::query()
            ->where('status', 'Approved')
            ->count();

        $totalVnd = MI_LiquidationItem::query()
            ->whereHas('report')
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
        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        if (!$user) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'You must be logged in to create a liquidation report.'
                );
        }

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
                ],

                'pcf_amount' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'items' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'items.*.ref_no' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'items.*.item_date' => [
                    'required',
                    'date',
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

            /*
            |--------------------------------------------------------------------------
            | Company
            |--------------------------------------------------------------------------
            */

            $companyId = null;

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
                    $companyId
                ) {

                    $liquidation = MI_Liquidation::create([

                        'title' =>
                            $validated['report_title'],

                        'date_prepared' =>
                            $validated['date_prepared'],

                        'exchange_rate' =>
                            $validated['exchange_rate'],

                        'pcf_amount' =>
                            $validated['pcf_amount'] ?? null,

                        'company_id' =>
                            $companyId,

                        /*
                        | Always use logged-in user.
                        */
                        'prepared_by' =>
                            $user->user_id,

                        'status' =>
                            'Pending',
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
                        $validated['items']
                        as $index => $item
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

                                $receiptPath = $file->store(
                                    'liquidations/receipts',
                                    'public'
                                );
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

                            'liquidation_id' =>
                                $liquidation->id,

                            'ref_no' =>
                                $refNo,

                            'line_no' =>
                                $index + 1,

                            'item_date' =>
                                $item['item_date'],

                            /*
                            | IMPORTANT:
                            | Never trust requested_by from frontend.
                            */
                            'requested_by' =>
                                $user->user_id,

                            'payee' =>
                                $item['payee'],

                            'expense_type' =>
                                $item['expense_type'],

                            'account_buyer' =>
                                $item['account_buyer'],

                            'amount_vnd' =>
                                $item['amount_vnd'],

                            'remarks' =>
                                $item['remarks'] ?? null,

                            'receipt_image' =>
                                $receiptPath,
                        ]);
                    }

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

        } catch (Throwable $e) {

            Log::error(
                'Liquidation save failed',
                [
                    'user_id' =>
                        $user->user_id ?? null,

                    'message' =>
                        $e->getMessage(),

                    'file' =>
                        $e->getFile(),

                    'line' =>
                        $e->getLine(),

                    'trace' =>
                        $e->getTraceAsString(),
                ]
            );

            if (config('app.debug')) {

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Liquidation save failed: ' .
                        $e->getMessage()
                    );
            }

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Unable to save the liquidation report. ' .
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

        $liquidation->load([
            'items.requestedBy',
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
public function edit($id): View
{
    $liquidation = MI_Liquidation::with([
        'items.requestedBy',
        'preparer',
        'company',
    ])->findOrFail($id);

    return view('mi_app.liquidation.edit', [
        'report' => $liquidation,
    ]);
}

    /**
     * Update liquidation report.
     */
    public function update(
        Request $request,
        MI_Liquidation $liquidation
    ): RedirectResponse {

        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        if (!$user) {
            abort(
                401,
                'You must be logged in.'
            );
        }

        try {

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
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
                ],

                'pcf_amount' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'items' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'items.*.ref_no' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'items.*.item_date' => [
                    'required',
                    'date',
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

            /*
            |--------------------------------------------------------------------------
            | Existing Items
            |--------------------------------------------------------------------------
            */

            $existingItems = $liquidation
                ->items()
                ->get();

            $oldItemsByLine = $existingItems
                ->keyBy('line_no');

            /*
            |--------------------------------------------------------------------------
            | Update
            |--------------------------------------------------------------------------
            */

            DB::transaction(
                function () use (
                    $validated,
                    $request,
                    $liquidation,
                    $user,
                    $oldItemsByLine
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Update Header
                    |--------------------------------------------------------------------------
                    */

                    $liquidation->update([

                        'title' =>
                            $validated['report_title'],

                        'date_prepared' =>
                            $validated['date_prepared'],

                        'exchange_rate' =>
                            $validated['exchange_rate'],

                        'pcf_amount' =>
                            $validated['pcf_amount'] ?? null,
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Date Prefix
                    |--------------------------------------------------------------------------
                    */

                    $datePrefix = Carbon::parse(
                        $validated['date_prepared']
                    )->format('Ymd');

                    /*
                    |--------------------------------------------------------------------------
                    | Delete Existing Items
                    |--------------------------------------------------------------------------
                    */

                    $liquidation
                        ->items()
                        ->delete();

                    /*
                    |--------------------------------------------------------------------------
                    | Recreate Items
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $validated['items']
                        as $index => $item
                    ) {

                        $lineNo = $index + 1;

                        $receiptPath = null;

                        /*
                        |--------------------------------------------------------------------------
                        | Preserve Existing Receipt
                        |--------------------------------------------------------------------------
                        */

                        if (
                            isset($oldItemsByLine[$lineNo]) &&
                            !empty(
                                $oldItemsByLine[$lineNo]->receipt_image
                            )
                        ) {

                            $receiptPath =
                                $oldItemsByLine[$lineNo]
                                    ->receipt_image;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | New Receipt
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

                                /*
                                | Delete previous receipt.
                                */

                                if (
                                    $receiptPath &&
                                    Storage::disk('public')
                                        ->exists($receiptPath)
                                ) {

                                    Storage::disk('public')
                                        ->delete($receiptPath);
                                }

                                /*
                                | Store replacement.
                                */

                                $receiptPath =
                                    $file->store(
                                        'liquidations/receipts',
                                        'public'
                                    );
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
                            $lineNo
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Create Updated Item
                        |--------------------------------------------------------------------------
                        */

                        MI_LiquidationItem::create([

                            'liquidation_id' =>
                                $liquidation->id,

                            'ref_no' =>
                                $refNo,

                            'line_no' =>
                                $lineNo,

                            'item_date' =>
                                $item['item_date'],

                            /*
                            | Server-side user.
                            */
                            'requested_by' =>
                                $user->user_id,

                            'payee' =>
                                $item['payee'],

                            'expense_type' =>
                                $item['expense_type'],

                            'account_buyer' =>
                                $item['account_buyer'],

                            'amount_vnd' =>
                                $item['amount_vnd'],

                            'remarks' =>
                                $item['remarks'] ?? null,

                            'receipt_image' =>
                                $receiptPath,
                        ]);
                    }
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
                    'Liquidation report updated successfully.'
                );

        } catch (Throwable $e) {

            Log::error(
                'Liquidation update failed',
                [
                    'liquidation_id' =>
                        $liquidation->id,

                    'user_id' =>
                        $user->user_id ?? null,

                    'message' =>
                        $e->getMessage(),

                    'file' =>
                        $e->getFile(),

                    'line' =>
                        $e->getLine(),

                    'trace' =>
                        $e->getTraceAsString(),
                ]
            );

            if (config('app.debug')) {

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Liquidation update failed: ' .
                        $e->getMessage()
                    );
            }

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Unable to update the liquidation report. ' .
                    'Please try again.'
                );
        }
    }


    /**
     * Delete liquidation report.
     */
    public function destroy(
        MI_Liquidation $liquidation
    ): RedirectResponse {

        try {

            DB::transaction(
                function () use ($liquidation) {

                    /*
                    |--------------------------------------------------------------------------
                    | Receipt Files
                    |--------------------------------------------------------------------------
                    */

                    $receiptPaths = $liquidation
                        ->items()
                        ->whereNotNull('receipt_image')
                        ->pluck('receipt_image')
                        ->filter()
                        ->values()
                        ->toArray();

                    /*
                    |--------------------------------------------------------------------------
                    | Delete Items
                    |--------------------------------------------------------------------------
                    */

                    $liquidation
                        ->items()
                        ->delete();

                    /*
                    |--------------------------------------------------------------------------
                    | Delete Liquidation
                    |--------------------------------------------------------------------------
                    */

                    $liquidation->delete();

                    /*
                    |--------------------------------------------------------------------------
                    | Delete Receipt Files
                    |--------------------------------------------------------------------------
                    */

                    foreach ($receiptPaths as $path) {

                        if (
                            Storage::disk('public')
                                ->exists($path)
                        ) {

                            Storage::disk('public')
                                ->delete($path);
                        }
                    }
                }
            );

            return redirect()
                ->route('liquidation.index')
                ->with(
                    'success',
                    'Liquidation report deleted successfully.'
                );

        } catch (Throwable $e) {

            Log::error(
                'Liquidation delete failed',
                [
                    'liquidation_id' =>
                        $liquidation->id,

                    'message' =>
                        $e->getMessage(),

                    'file' =>
                        $e->getFile(),

                    'line' =>
                        $e->getLine(),
                ]
            );

            return back()
                ->with(
                    'error',
                    config('app.debug')
                        ? 'Unable to delete liquidation: ' .
                          $e->getMessage()
                        : 'Unable to delete the liquidation report.'
                );
        }
    }
}