<?php

namespace App\Http\Controllers;

use App\Models\MI_Liquidation;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class Accounting_LiquidationController extends Controller
{
    /**
     * Display liquidation reports for Accounting.
     */
    public function downloadPdf(MI_Liquidation $liquidation)
    {
        $liquidation->load('items', 'preparer', 'company');

        $pdf = Pdf::loadView('mi_app.liquidation.liquidation_pdf', compact('liquidation'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('Liquidation-' . str_pad($liquidation->id, 6, '0', STR_PAD_LEFT) . '.pdf');
    }
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
        | - Expense type
        | - Account buyer
        |
        */

        if ($request->filled('search')) {

            $search = trim(
                (string) $request->input('search')
            );

            $query->where(function ($q) use ($search) {

                // Report title
                $q->where(
                    'title',
                    'like',
                    "%{$search}%"
                );

                // Liquidation ID
                if (is_numeric($search)) {
                    $q->orWhere(
                        'id',
                        (int) $search
                    );
                }

                // Item information
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
        | Company Filter
        |--------------------------------------------------------------------------
        |
        | Since this controller will be accessible by both MMC and MI,
        | the company.context middleware should determine the current
        | company context.
        |
        */

        $user = auth()->user();

        if (
            $user &&
            method_exists($user, 'currentCompany')
        ) {

            $company = $user->currentCompany();

            if ($company) {

                $query->where(
                    'company_id',
                    $company->company_id
                );
            }
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

        $statisticsQuery = MI_Liquidation::query();

        // Keep dashboard statistics within current company
        if (
            $user &&
            method_exists($user, 'currentCompany')
        ) {

            $company = $user->currentCompany();

            if ($company) {

                $statisticsQuery->where(
                    'company_id',
                    $company->company_id
                );
            }
        }

        $pendingCount = (clone $statisticsQuery)
            ->where('status', 'Pending')
            ->count();

        $approvedCount = (clone $statisticsQuery)
            ->where('status', 'Approved')
            ->count();

        $totalVnd = \App\Models\MI_LiquidationItem::query()
            ->whereHas('report', function ($q) use ($user) {

                if (
                    $user &&
                    method_exists($user, 'currentCompany')
                ) {

                    $company = $user->currentCompany();

                    if ($company) {

                        $q->where(
                            'company_id',
                            $company->company_id
                        );
                    }
                }
            })
            ->sum('amount_vnd');

        /*
        |--------------------------------------------------------------------------
        | Return Accounting View
        |--------------------------------------------------------------------------
        */

        return view(
            'accounting.liquidation.index',
            compact(
                'reports',
                'pendingCount',
                'approvedCount',
                'totalVnd'
            )
        );
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
        | Company Context Security
        |--------------------------------------------------------------------------
        |
        | Prevent Accounting users from another company from viewing
        | this liquidation.
        |
        */

        $user = auth()->user();

        if (
            $user &&
            method_exists($user, 'currentCompany')
        ) {

            $company = $user->currentCompany();

            if (
                $company &&
                (int) $liquidation->company_id !==
                (int) $company->company_id
            ) {

                abort(403, 'You are not authorized to view this liquidation.');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Return Accounting Detail View
        |--------------------------------------------------------------------------
        */

        return view(
            'accounting.liquidation.show',
            [
                'liquidation' => $liquidation,
            ]
        );
    }


    /**
     * Generate liquidation PDF.
     */
    public function liquidation_pdf(
        MI_Liquidation $liquidation
    ) {

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
        | Company Context Security
        |--------------------------------------------------------------------------
        */

        $user = auth()->user();

        if (
            $user &&
            method_exists($user, 'currentCompany')
        ) {

            $company = $user->currentCompany();

            if (
                $company &&
                (int) $liquidation->company_id !==
                (int) $company->company_id
            ) {

                abort(403, 'You are not authorized to download this liquidation.');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Generate PDF
        |--------------------------------------------------------------------------
        */

        $pdf = Pdf::loadView(
            'accounting.liquidation.liquidation_pdf',
            compact('liquidation')
        )->setPaper(
            'a4',
            'portrait'
        );

        /*
        |--------------------------------------------------------------------------
        | Download PDF
        |--------------------------------------------------------------------------
        */

        return $pdf->download(
            'Liquidation-' .
            str_pad(
                $liquidation->id,
                6,
                '0',
                STR_PAD_LEFT
            ) .
            '.pdf'
        );
    }
}