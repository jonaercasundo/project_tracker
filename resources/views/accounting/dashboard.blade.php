<x-accounting_app>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
    href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap"
    rel="stylesheet"
>

<style>

    /* ============================================================
       ACCOUNTING DASHBOARD
    ============================================================ */

    .dash {
        --dash-primary: #111827;
        --dash-secondary: #64748b;
        --dash-border: #e2e8f0;
        --dash-bg: #f8fafc;
        --dash-soft: #f1f5f9;

        --dash-blue: #2563eb;
        --dash-blue-dark: #1d4ed8;
        --dash-blue-soft: #eff6ff;

        --dash-green: #059669;
        --dash-green-soft: #ecfdf5;

        --dash-red: #dc2626;
        --dash-red-soft: #fff1f2;

        --dash-yellow: #a16207;
        --dash-yellow-soft: #fefce8;

        min-height: 100vh;
        background: var(--dash-bg);
        color: var(--dash-primary);

        font-family: "Inter", sans-serif;
    }

    .dash *,
    .dash *::before,
    .dash *::after {
        box-sizing: border-box;
    }

    .dash-mono {
        font-family: "JetBrains Mono", monospace;
    }

    .dash-container {
        width: min(100%, 1450px);
        margin: 0 auto;
        padding: 28px 24px 55px;
    }

    /* ============================================================
       HEADER
    ============================================================ */

    .dash-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;

        margin-bottom: 25px;
    }

    .dash-breadcrumb {
        display: flex;
        align-items: center;
        gap: 7px;

        margin-bottom: 8px;

        color: #94a3b8;

        font-size: 11px;
        font-weight: 700;

        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .dash-title {
        margin: 0;

        color: var(--dash-primary);

        font-family: "Space Grotesk", sans-serif;

        font-size: 28px;
        line-height: 1.15;
        font-weight: 700;

        letter-spacing: -.5px;
    }

    .dash-subtitle {
        margin: 6px 0 0;

        color: var(--dash-secondary);

        font-size: 13px;
        line-height: 1.5;
    }

    .dash-header-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;

        gap: 9px;

        flex-wrap: wrap;
    }

    /* ============================================================
       BUTTONS
    ============================================================ */

    .dash-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;

        min-height: 40px;

        padding: 9px 14px;

        border: 1px solid #cbd5e1;
        border-radius: 9px;

        background: white;
        color: #475569;

        font-size: 12px;
        font-weight: 800;

        text-decoration: none;

        cursor: pointer;

        transition: .15s ease;
    }

    .dash-btn:hover {
        border-color: #94a3b8;
        background: #f8fafc;

        transform: translateY(-1px);
    }

    .dash-btn-primary {
        border-color: var(--dash-blue);
        background: var(--dash-blue);
        color: white;
    }

    .dash-btn-primary:hover {
        border-color: var(--dash-blue-dark);
        background: var(--dash-blue-dark);
        color: white;
    }

    /* ============================================================
       SUMMARY CARDS
    ============================================================ */

    .dash-summary {
        display: grid;

        grid-template-columns:
            repeat(5, minmax(0, 1fr));

        gap: 15px;

        margin-bottom: 20px;
    }

    .dash-summary-card {
        min-width: 0;

        padding: 17px;

        border: 1px solid var(--dash-border);
        border-radius: 14px;

        background: white;

        box-shadow:
            0 4px 20px rgba(15, 23, 42, .035);
    }

    .dash-summary-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;

        gap: 8px;
    }

    .dash-summary-label {
        color: #94a3b8;

        font-size: 10px;
        font-weight: 800;

        letter-spacing: .55px;
        text-transform: uppercase;
    }

    .dash-summary-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        width: 26px;
        height: 26px;
        flex: 0 0 26px;

        border-radius: 7px;
    }

    .dash-icon-blue   { background: var(--dash-blue-soft);   color: var(--dash-blue); }
    .dash-icon-green  { background: var(--dash-green-soft);  color: var(--dash-green); }
    .dash-icon-yellow { background: var(--dash-yellow-soft); color: var(--dash-yellow); }
    .dash-icon-red    { background: var(--dash-red-soft);    color: var(--dash-red); }
    .dash-icon-slate  { background: var(--dash-soft);        color: #64748b; }

    .dash-summary-value {
        margin-top: 9px;

        color: #0f172a;

        font-family: "JetBrains Mono", monospace;

        font-size: 18px;
        font-weight: 800;
    }

    .dash-summary-sub {
        margin-top: 4px;

        color: #64748b;

        font-family: "JetBrains Mono", monospace;

        font-size: 11px;
    }

    .dash-summary-trend {
        margin-top: 4px;

        font-size: 10.5px;
        font-weight: 700;
    }

    .dash-trend-up   { color: var(--dash-green); }
    .dash-trend-down { color: var(--dash-red); }
    .dash-trend-flat { color: #94a3b8; }

    /* ============================================================
       MAIN GRID
    ============================================================ */

    .dash-grid {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
        gap: 20px;

        align-items: start;
    }

    /* ============================================================
       CARD
    ============================================================ */

    .dash-card {
        margin-bottom: 20px;

        overflow: hidden;

        border: 1px solid var(--dash-border);
        border-radius: 16px;

        background: white;

        box-shadow:
            0 4px 20px rgba(15, 23, 42, .035);
    }

    .dash-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;

        gap: 15px;

        padding: 17px 20px;

        background: #f8fafc;

        border-bottom: 1px solid var(--dash-border);
    }

    .dash-card-title {
        margin: 0;

        color: #1e293b;

        font-family: "Space Grotesk", sans-serif;

        font-size: 15px;
        font-weight: 700;
    }

    .dash-card-description {
        margin-top: 3px;

        color: #64748b;

        font-size: 11px;
    }

    .dash-card-link {
        color: var(--dash-blue);

        font-size: 11px;
        font-weight: 800;

        text-decoration: none;

        white-space: nowrap;
    }

    .dash-card-link:hover {
        text-decoration: underline;
    }

    /* ============================================================
       RECENT REPORTS TABLE
    ============================================================ */

    .dash-table {
        width: 100%;
        border-collapse: collapse;
    }

    .dash-table th {
        padding: 10px 20px;

        background: #f8fafc;
        border-bottom: 1px solid var(--dash-border);

        color: #94a3b8;

        font-size: 9.5px;
        font-weight: 800;

        text-align: left;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .dash-table td {
        padding: 12px 20px;

        border-bottom: 1px solid #f1f5f9;

        font-size: 12px;
        color: #334155;

        vertical-align: middle;
    }

    .dash-table tr:last-child td {
        border-bottom: none;
    }

    .dash-table tr {
        transition: background .12s ease;
    }

    .dash-table tbody tr:hover {
        background: #f8fafc;
    }

    .dash-row-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .dash-report-title {
        font-weight: 700;
        color: #1e293b;
    }

    .dash-report-id {
        margin-top: 2px;

        color: #94a3b8;

        font-family: "JetBrains Mono", monospace;

        font-size: 10px;
    }

    .dash-amount {
        font-family: "JetBrains Mono", monospace;
        font-weight: 700;
        color: #0f172a;
    }

    .dash-amount-sub {
        margin-top: 2px;

        font-family: "JetBrains Mono", monospace;
        font-size: 10px;
        color: #94a3b8;
    }

    /* ============================================================
       STATUS BADGE
    ============================================================ */

    .dash-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;

        padding: 5px 10px;

        border-radius: 999px;

        font-size: 10.5px;
        font-weight: 800;
    }

    .dash-status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    .dash-status-pending {
        background: var(--dash-yellow-soft);
        color: var(--dash-yellow);
    }

    .dash-status-pending .dash-status-dot { background: var(--dash-yellow); }

    .dash-status-approved {
        background: var(--dash-green-soft);
        color: #047857;
    }

    .dash-status-approved .dash-status-dot { background: var(--dash-green); }

    .dash-status-rejected {
        background: var(--dash-red-soft);
        color: #b91c1c;
    }

    .dash-status-rejected .dash-status-dot { background: var(--dash-red); }

    .dash-status-default {
        background: var(--dash-soft);
        color: #64748b;
    }

    .dash-status-default .dash-status-dot { background: #94a3b8; }

    /* ============================================================
       SIDE PANELS
    ============================================================ */

    .dash-panel-body {
        padding: 18px 20px;
    }

    /* ---- status breakdown bars ---- */

    .dash-breakdown-row {
        margin-bottom: 14px;
    }

    .dash-breakdown-row:last-child {
        margin-bottom: 0;
    }

    .dash-breakdown-top {
        display: flex;
        align-items: center;
        justify-content: space-between;

        margin-bottom: 6px;

        font-size: 11.5px;
        font-weight: 700;
        color: #334155;
    }

    .dash-breakdown-count {
        font-family: "JetBrains Mono", monospace;
        color: #64748b;
        font-weight: 700;
    }

    .dash-bar-track {
        width: 100%;
        height: 8px;

        border-radius: 999px;

        background: #f1f5f9;

        overflow: hidden;
    }

    .dash-bar-fill {
        height: 100%;
        border-radius: 999px;
    }

    .dash-bar-pending  { background: var(--dash-yellow); }
    .dash-bar-approved { background: var(--dash-green); }
    .dash-bar-rejected { background: var(--dash-red); }

    /* ---- expense type breakdown ---- */

    .dash-etype-row {
        display: flex;
        align-items: center;
        gap: 10px;

        margin-bottom: 12px;
    }

    .dash-etype-row:last-child {
        margin-bottom: 0;
    }

    .dash-etype-label {
        flex: 0 0 108px;

        font-size: 11px;
        font-weight: 700;
        color: #334155;

        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dash-etype-track {
        flex: 1 1 auto;

        height: 8px;

        border-radius: 999px;

        background: #f1f5f9;

        overflow: hidden;
    }

    .dash-etype-fill {
        height: 100%;
        border-radius: 999px;
        background: var(--dash-blue);
    }

    .dash-etype-value {
        flex: 0 0 auto;

        font-family: "JetBrains Mono", monospace;
        font-size: 10.5px;
        font-weight: 700;
        color: #64748b;

        white-space: nowrap;
    }

    /* ---- quick actions ---- */

    .dash-action-link {
        display: flex;
        align-items: center;
        gap: 10px;

        padding: 11px 12px;

        border: 1px solid var(--dash-border);
        border-radius: 10px;

        margin-bottom: 9px;

        text-decoration: none;
        color: #334155;

        font-size: 12px;
        font-weight: 700;

        transition: .12s ease;
    }

    .dash-action-link:last-child {
        margin-bottom: 0;
    }

    .dash-action-link:hover {
        border-color: #94a3b8;
        background: #f8fafc;
        transform: translateX(2px);
    }

    .dash-action-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        width: 28px;
        height: 28px;
        flex: 0 0 28px;

        border-radius: 8px;

        background: var(--dash-blue-soft);
        color: var(--dash-blue);
    }

    /* ============================================================
       EMPTY STATE
    ============================================================ */

    .dash-empty-state {
        padding: 45px 25px;

        text-align: center;

        color: #94a3b8;

        font-size: 12px;
    }

    /* ============================================================
       RESPONSIVE
    ============================================================ */

    @media (max-width: 1150px) {

        .dash-summary {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .dash-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 700px) {

        .dash-container {
            padding: 18px 14px 40px;
        }

        .dash-header {
            flex-direction: column;
        }

        .dash-header-actions {
            width: 100%;
        }

        .dash-header-actions .dash-btn {
            flex: 1;
        }

        .dash-title {
            font-size: 23px;
        }

        .dash-summary {
            grid-template-columns: 1fr;
        }

        .dash-table th:nth-child(3),
        .dash-table td:nth-child(3),
        .dash-table th:nth-child(4),
        .dash-table td:nth-child(4) {
            display: none;
        }
    }

</style>


@php

    /*
    |--------------------------------------------------------------------------
    | DEFAULTS
    |--------------------------------------------------------------------------
    | Falls back gracefully if the controller hasn't supplied every variable
    | yet, so this view never hard-errors during setup.
    */

    $totalLiquidatedVnd = (float) ($totalLiquidatedVnd ?? 0);
    $totalLiquidatedUsd = (float) ($totalLiquidatedUsd ?? 0);

    $totalPcfVnd = (float) ($totalPcfVnd ?? 0);
    $totalPcfUsd = (float) ($totalPcfUsd ?? 0);

    $totalCashOnHandVnd = (float) ($totalCashOnHandVnd ?? 0);
    $totalCashOnHandUsd = (float) ($totalCashOnHandUsd ?? 0);

    $pendingCount  = (int) ($pendingCount  ?? 0);
    $approvedCount = (int) ($approvedCount ?? 0);
    $rejectedCount = (int) ($rejectedCount ?? 0);

    $totalReports = $pendingCount + $approvedCount + $rejectedCount;

    $reportsThisMonth = (int) ($reportsThisMonth ?? 0);

    $recentReports = $recentReports ?? collect();

    // expenseByType expected shape: ['Type Name' => 1234567.0, ...]
    $expenseByType = collect($expenseByType ?? []);
    $maxExpenseType = $expenseByType->max() ?: 1;

    $statusClassMap = [
        'pending'  => 'dash-status-pending',
        'approved' => 'dash-status-approved',
        'rejected' => 'dash-status-rejected',
    ];

    $pendingPct  = $totalReports > 0 ? round(($pendingCount  / $totalReports) * 100) : 0;
    $approvedPct = $totalReports > 0 ? round(($approvedCount / $totalReports) * 100) : 0;
    $rejectedPct = $totalReports > 0 ? round(($rejectedCount / $totalReports) * 100) : 0;

@endphp


<div class="dash">

    <div class="dash-container">


        {{-- ========================================================
             HEADER
        ========================================================= --}}

        <div class="dash-header">

            <div>

                <div class="dash-breadcrumb">
                    <span>Accounting</span>
                    <span>/</span>
                    <span>Dashboard</span>
                </div>

                <h1 class="dash-title">Accounting Dashboard</h1>

                <p class="dash-subtitle">
                    Overview of liquidation reports, cash position, and pending approvals.
                </p>

            </div>


            <div class="dash-header-actions">

                <a href="{{ route('liquidation.index') }}" class="dash-btn">

                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                    </svg>

                    All Reports

                </a>

                <a href="{{ route('liquidation.create') }}" class="dash-btn dash-btn-primary">

                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>

                    New Liquidation Report

                </a>

            </div>

        </div>


        {{-- ========================================================
             SUMMARY CARDS
        ========================================================= --}}

        <div class="dash-summary">

            {{-- TOTAL LIQUIDATED --}}

            <div class="dash-summary-card">

                <div class="dash-summary-top">
                    <div class="dash-summary-label">Total Liquidated</div>
                    <div class="dash-summary-icon dash-icon-blue">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2m9-8a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <div class="dash-summary-value">₫{{ number_format($totalLiquidatedVnd, 0) }}</div>
                <div class="dash-summary-sub">${{ number_format($totalLiquidatedUsd, 2) }}</div>

            </div>


            {{-- PENDING REPORTS --}}

            <div class="dash-summary-card">

                <div class="dash-summary-top">
                    <div class="dash-summary-label">Pending Reports</div>
                    <div class="dash-summary-icon dash-icon-yellow">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <div class="dash-summary-value">{{ $pendingCount }}</div>
                <div class="dash-summary-sub">Awaiting approval</div>

            </div>


            {{-- APPROVED REPORTS --}}

            <div class="dash-summary-card">

                <div class="dash-summary-top">
                    <div class="dash-summary-label">Approved Reports</div>
                    <div class="dash-summary-icon dash-icon-green">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <div class="dash-summary-value">{{ $approvedCount }}</div>
                <div class="dash-summary-sub">{{ $reportsThisMonth }} this month</div>

            </div>


            {{-- TOTAL PCF ISSUED --}}

            <div class="dash-summary-card">

                <div class="dash-summary-top">
                    <div class="dash-summary-label">PCF Issued</div>
                    <div class="dash-summary-icon dash-icon-slate">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>

                <div class="dash-summary-value">₫{{ number_format($totalPcfVnd, 0) }}</div>
                <div class="dash-summary-sub">${{ number_format($totalPcfUsd, 2) }}</div>

            </div>


            {{-- CASH ON HAND --}}

            <div class="dash-summary-card">

                <div class="dash-summary-top">
                    <div class="dash-summary-label">Cash on Hand</div>
                    <div class="dash-summary-icon dash-icon-blue">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>

                <div class="dash-summary-value">₫{{ number_format($totalCashOnHandVnd, 0) }}</div>
                <div class="dash-summary-sub">${{ number_format($totalCashOnHandUsd, 2) }}</div>

            </div>

        </div>


        {{-- ========================================================
             MAIN GRID: RECENT REPORTS + SIDE PANELS
        ========================================================= --}}

        <div class="dash-grid">


            {{-- ====================================================
                 LEFT: RECENT REPORTS
            ===================================================== --}}

            <div>

                <div class="dash-card">

                    <div class="dash-card-header">

                        <div>
                            <h2 class="dash-card-title">Recent Liquidation Reports</h2>
                            <div class="dash-card-description">Latest submissions across all companies</div>
                        </div>

                        <a href="{{ route('liquidation.index') }}" class="dash-card-link">
                            View all →
                        </a>

                    </div>


                    @if($recentReports->count())

                        <table class="dash-table">

                            <thead>
                                <tr>
                                    <th>Report</th>
                                    <th>Company</th>
                                    <th>Prepared By</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>

                                @foreach($recentReports as $report)

                                    @php
                                        $rowStatus = strtolower(trim($report->status ?? 'pending'));
                                        $rowStatusClass = $statusClassMap[$rowStatus] ?? 'dash-status-default';
                                        $rowTotalVnd = (float) ($report->total_vnd ?? 0);
                                        $rowTotalUsd = (float) ($report->total_usd ?? 0);
                                    @endphp

                                    <tr>
                                        <td>
                                            <a href="{{ route('liquidation.show', $report->id) }}" class="dash-row-link">
                                                <div class="dash-report-title">{{ $report->title }}</div>
                                                <div class="dash-report-id">
                                                    LIQ-{{ str_pad($report->id, 6, '0', STR_PAD_LEFT) }}
                                                </div>
                                            </a>
                                        </td>
                                        <td>{{ $report->company?->name ?? '—' }}</td>
                                        <td>{{ $report->preparer?->name ?? '—' }}</td>
                                        <td class="dash-mono">
                                            {{ $report->date_prepared ? $report->date_prepared->format('M d, Y') : '—' }}
                                        </td>
                                        <td>
                                            <div class="dash-amount">₫{{ number_format($rowTotalVnd, 0) }}</div>
                                            <div class="dash-amount-sub">${{ number_format($rowTotalUsd, 2) }}</div>
                                        </td>
                                        <td>
                                            <span class="dash-status {{ $rowStatusClass }}">
                                                <span class="dash-status-dot"></span>
                                                {{ ucfirst($rowStatus) }}
                                            </span>
                                        </td>
                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    @else

                        <div class="dash-empty-state">
                            No liquidation reports yet. Create your first report to get started.
                        </div>

                    @endif

                </div>


                {{-- ================================================
                     EXPENSE TYPE BREAKDOWN
                ================================================= --}}

                <div class="dash-card">

                    <div class="dash-card-header">
                        <div>
                            <h2 class="dash-card-title">Expenses by Type</h2>
                            <div class="dash-card-description">Distribution across expense categories</div>
                        </div>
                    </div>

                    <div class="dash-panel-body">

                        @if($expenseByType->isNotEmpty())

                            @foreach($expenseByType as $typeName => $typeTotal)

                                @php
                                    $typePct = $maxExpenseType > 0
                                        ? max(4, round(($typeTotal / $maxExpenseType) * 100))
                                        : 0;
                                @endphp

                                <div class="dash-etype-row">
                                    <div class="dash-etype-label">{{ $typeName }}</div>
                                    <div class="dash-etype-track">
                                        <div class="dash-etype-fill" style="width: {{ $typePct }}%;"></div>
                                    </div>
                                    <div class="dash-etype-value">₫{{ number_format($typeTotal, 0) }}</div>
                                </div>

                            @endforeach

                        @else

                            <div class="dash-empty-state">No expense data available yet.</div>

                        @endif

                    </div>

                </div>

            </div>


            {{-- ====================================================
                 RIGHT: STATUS BREAKDOWN + QUICK ACTIONS
            ===================================================== --}}

            <div>

                {{-- REPORT STATUS --}}

                <div class="dash-card">

                    <div class="dash-card-header">
                        <div>
                            <h2 class="dash-card-title">Report Status</h2>
                            <div class="dash-card-description">{{ $totalReports }} total reports</div>
                        </div>
                    </div>

                    <div class="dash-panel-body">

                        <div class="dash-breakdown-row">
                            <div class="dash-breakdown-top">
                                <span>Pending</span>
                                <span class="dash-breakdown-count">{{ $pendingCount }} ({{ $pendingPct }}%)</span>
                            </div>
                            <div class="dash-bar-track">
                                <div class="dash-bar-fill dash-bar-pending" style="width: {{ $pendingPct }}%;"></div>
                            </div>
                        </div>

                        <div class="dash-breakdown-row">
                            <div class="dash-breakdown-top">
                                <span>Approved</span>
                                <span class="dash-breakdown-count">{{ $approvedCount }} ({{ $approvedPct }}%)</span>
                            </div>
                            <div class="dash-bar-track">
                                <div class="dash-bar-fill dash-bar-approved" style="width: {{ $approvedPct }}%;"></div>
                            </div>
                        </div>

                        <div class="dash-breakdown-row">
                            <div class="dash-breakdown-top">
                                <span>Rejected</span>
                                <span class="dash-breakdown-count">{{ $rejectedCount }} ({{ $rejectedPct }}%)</span>
                            </div>
                            <div class="dash-bar-track">
                                <div class="dash-bar-fill dash-bar-rejected" style="width: {{ $rejectedPct }}%;"></div>
                            </div>
                        </div>

                    </div>

                </div>


                {{-- QUICK ACTIONS --}}

                <div class="dash-card">

                    <div class="dash-card-header">
                        <div>
                            <h2 class="dash-card-title">Quick Actions</h2>
                        </div>
                    </div>

                    <div class="dash-panel-body">

                        <a href="{{ route('liquidation.create') }}" class="dash-action-link">
                            <span class="dash-action-icon">
                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </span>
                            New Liquidation Report
                        </a>

                        <a href="{{ route('liquidation.index', ['status' => 'pending']) }}" class="dash-action-link">
                            <span class="dash-action-icon">
                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            Review Pending Reports
                        </a>

                        <a href="{{ route('liquidation.index') }}" class="dash-action-link">
                            <span class="dash-action-icon">
                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                                </svg>
                            </span>
                            Browse All Reports
                        </a>

                    </div>

                </div>

            </div>

        </div>


    </div>

</div>

</x-accounting_app>