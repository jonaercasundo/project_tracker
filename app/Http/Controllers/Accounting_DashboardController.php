<?php

namespace App\Http\Controllers;

use App\Models\MI_Liquidation;
use App\Models\MI_LiquidationItem;
use Illuminate\Http\Request;

class Accounting_DashboardController extends Controller
{
    /**
     * Display the accounting dashboard.
     */
    public function dashboard(Request $request)
    {
        // ------------------------------------------------------------
        // Base query: all liquidation reports with the relations the
        // dashboard view needs (company + preparer for the table).
        // ------------------------------------------------------------

        $reports = MI_Liquidation::with(['company', 'preparer'])->get();

        // ------------------------------------------------------------
        // Status counts
        // ------------------------------------------------------------

        $pendingCount  = $reports->filter(fn ($r) => strtolower(trim($r->status ?? 'pending')) === 'pending')->count();
        $approvedCount = $reports->filter(fn ($r) => strtolower(trim($r->status ?? '')) === 'approved')->count();
        $rejectedCount = $reports->filter(fn ($r) => strtolower(trim($r->status ?? '')) === 'rejected')->count();

        // ------------------------------------------------------------
        // Totals (VND / USD)
        // ------------------------------------------------------------

        $totalLiquidatedVnd = (float) $reports->sum('total_vnd');
        $totalLiquidatedUsd = (float) $reports->sum('total_usd');

        $totalPcfVnd = (float) $reports->sum('pcf_amount');

        $totalPcfUsd = (float) $reports->sum(function ($report) {
            $rate = (float) ($report->exchange_rate ?? 0);
            $pcf  = (float) ($report->pcf_amount ?? 0);

            return $rate > 0 ? $pcf / $rate : 0;
        });

        $totalCashOnHandVnd = (float) $reports->sum('cash_on_hand_vnd');
        $totalCashOnHandUsd = (float) $reports->sum('cash_on_hand_usd');

        // ------------------------------------------------------------
        // Approved reports prepared within the current calendar month
        // ------------------------------------------------------------

        $reportsThisMonth = $reports
            ->filter(function ($report) {
                $isApproved = strtolower(trim($report->status ?? '')) === 'approved';

                $preparedThisMonth = $report->date_prepared
                    && $report->date_prepared->isSameMonth(now())
                    && $report->date_prepared->isSameYear(now());

                return $isApproved && $preparedThisMonth;
            })
            ->count();

        // ------------------------------------------------------------
        // Recent reports (latest 8, newest first)
        // ------------------------------------------------------------

        $recentReports = $reports
            ->sortByDesc(fn ($report) => $report->created_at)
            ->take(8)
            ->values();

        // ------------------------------------------------------------
        // Expense breakdown by type, across all liquidation items
        // Sorted descending so the largest categories show first.
        // ------------------------------------------------------------

        $expenseByType = MI_LiquidationItem::query()
            ->selectRaw('expense_type, SUM(amount_vnd) as total')
            ->whereNotNull('expense_type')
            ->groupBy('expense_type')
            ->orderByDesc('total')
            ->pluck('total', 'expense_type')
            ->map(fn ($total) => (float) $total);

        // ------------------------------------------------------------
        // Render
        // ------------------------------------------------------------

        return view('accounting.dashboard', [
            'totalLiquidatedVnd' => $totalLiquidatedVnd,
            'totalLiquidatedUsd' => $totalLiquidatedUsd,
            'totalPcfVnd'        => $totalPcfVnd,
            'totalPcfUsd'        => $totalPcfUsd,
            'totalCashOnHandVnd' => $totalCashOnHandVnd,
            'totalCashOnHandUsd' => $totalCashOnHandUsd,
            'pendingCount'       => $pendingCount,
            'approvedCount'      => $approvedCount,
            'rejectedCount'      => $rejectedCount,
            'reportsThisMonth'   => $reportsThisMonth,
            'recentReports'      => $recentReports,
            'expenseByType'      => $expenseByType,
        ]);
    }
}