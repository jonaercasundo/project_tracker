<?php

namespace App\Services;

use App\Models\BudgetRequest;
use App\Models\Liquidation;
use App\Models\MI_Liquidation;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AccountingWorkspace
{
    public const SECTIONS = [
        'tasks' => ['My Tasks', 'mi.accounting.dashboard.view'],
        'budgets' => ['Budget Requests', 'mi.budget.view'],
        'budget-review' => ['Budget For Review', 'mi.budget.view'],
        'returned' => ['Returned / Rejected', 'mi.liquidation.view'],
        'releases' => ['Fund Releases', 'mi.budget.view'],
        'acknowledgment' => ['Awaiting Acknowledgment', 'mi.budget.view'],
        'cash' => ['Cash / PCF', 'mi.liquidation.view'],
        'liquidations' => ['Liquidations', 'mi.liquidation.view'],
        'liquidation-review' => ['Liquidation For Review', 'mi.liquidation.view'],
        'missing' => ['Missing Requirements', 'mi.liquidation.view'],
        'settlement' => ['Settlement', 'mi.settlement.view'],
        'transactions' => ['Transactions', 'mi.financial-reports.view'],
        'expenses' => ['Expense Records', 'mi.liquidation.view'],
        'documents' => ['Documents & Receipts', 'mi.liquidation.view'],
        'reports' => ['Financial Reports', 'mi.financial-reports.view'],
        'liquidation-reports' => ['Liquidation Reports', 'mi.financial-reports.view'],
        'audit' => ['Audit Trail', 'mi.audit.view'],
    ];

    /** Company attribution and capability scoping occur before pagination or counting. */
    public function records(User $user): Builder
    {
        $companyId = $user->currentCompany()?->getKey();
        abort_unless($user->hasRole('accounting') && $user->currentCompany()?->code === 'MI' && $user->currentCompany()->is_active, 403);
        $budgets = DB::table('budget_requests as b')->join('users as u', 'u.user_id', '=', 'b.employee_id')
            ->whereIn('b.id', BudgetRequest::where('company_id', $companyId)->select('id'))
            ->selectRaw("b.id, 'budget' as type, b.control_id as reference, u.name as employee, b.employee_id, b.department, b.objectives as purpose, b.budget_total as amount, 'Unspecified' as currency, b.status, b.created_at, COALESCE(b.received_at, b.released_at, b.noted_at, b.approved_at, b.created_at) as waiting_since, CASE WHEN b.status = 'approved' AND b.noted_at IS NULL THEN 'budget-review' WHEN b.status = 'approved' AND b.noted_at IS NOT NULL AND b.noted_by IS NOT NULL AND b.released_at IS NULL THEN 'releases' WHEN b.status = 'released' AND b.received_at IS NULL THEN 'acknowledgment' ELSE 'visibility' END as stage, 0 as missing")
            ->selectRaw('CASE WHEN b.status = ? AND ((b.noted_at IS NULL AND ? = 1) OR (b.noted_at IS NOT NULL AND b.noted_by IS NOT NULL AND b.released_at IS NULL AND ? = 1)) THEN 1 ELSE 0 END as actionable', ['approved', (int) $user->can('mi.budget.review'), (int) $user->can('mi.budget.release')]);
        if (! $user->can('mi.budget.view')) {
            $budgets->whereRaw('1 = 0');
        }
        $travel = DB::table('liquidations as l')->join('budget_requests as b', 'b.id', '=', 'l.budget_request_id')->join('users as u', 'u.user_id', '=', 'b.employee_id')
            ->whereIn('l.id', Liquidation::where('company_id', $companyId)->select('id'))
            ->whereIn('b.id', BudgetRequest::where('company_id', $companyId)->select('id'))
            ->selectRaw("l.id, 'travel' as type, b.control_id as reference, u.name as employee, b.employee_id, b.department, b.objectives as purpose, l.actual_total as amount, 'Unspecified' as currency, l.status, l.created_at, COALESCE(l.approved_at, l.noted_at, l.submitted_at, l.created_at) as waiting_since, CASE WHEN l.status = 'submitted' AND l.noted_at IS NULL THEN 'liquidation-review' WHEN l.status = 'approved' THEN 'settlement' ELSE 'visibility' END as stage, CASE WHEN EXISTS (SELECT 1 FROM liquidation_items i WHERE i.liquidation_id = l.id AND i.receipt_attached = 'no') THEN 1 ELSE 0 END as missing")
            ->selectRaw('CASE WHEN l.status = ? AND l.noted_at IS NULL AND ? = 1 THEN 1 WHEN l.status = ? AND ? = 1 AND NOT EXISTS (SELECT 1 FROM financial_settlements s WHERE s.liquidation_id = l.id) THEN 1 ELSE 0 END as actionable', ['submitted', (int) $user->can('mi.liquidation.review'), 'approved', (int) ($user->can('mi.travel.settle') && config('mi_financial.settlement_recording_enabled'))]);
        if (! $user->can('mi.liquidation.view')) {
            $travel->whereRaw('1 = 0');
        }
        $ordinary = DB::table('mi_liquidations as o')->leftJoin('users as u', 'u.user_id', '=', 'o.prepared_by')
            ->whereIn('o.id', MI_Liquidation::where('company_id', $companyId)->select('id'))
            ->selectRaw("o.id, 'ordinary' as type, o.title as reference, u.name as employee, o.prepared_by as employee_id, NULL as department, o.title as purpose, COALESCE((SELECT SUM(i.amount_vnd) FROM mi_liquidation_items i WHERE i.liquidation_id = o.id), 0) as amount, 'VND' as currency, o.status, o.created_at, o.created_at as waiting_since, 'visibility' as stage, CASE WHEN EXISTS (SELECT 1 FROM mi_liquidation_items i WHERE i.liquidation_id = o.id AND (i.receipt_image IS NULL OR i.receipt_image = '')) THEN 1 ELSE 0 END as missing, 0 as actionable");
        if (! $user->can('mi.liquidation.view')) {
            $ordinary->whereRaw('1 = 0');
        }

        return DB::query()->fromSub($budgets->unionAll($travel)->unionAll($ordinary), 'records');
    }

    /** @param array<string, mixed> $filters */
    public function filtered(User $user, array $filters): Builder
    {
        return $this->records($user)
            ->when($filters['employee'] ?? null, fn ($q, $v) => $q->where('employee_id', $v))
            ->when($filters['department'] ?? null, fn ($q, $v) => $q->where('department', $v))
            ->when($filters['workflow_status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filters['reference'] ?? null, fn ($q, $v) => $q->where('reference', 'like', '%'.$v.'%'))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when(isset($filters['amount_min']), fn ($q) => $q->whereRaw('CAST(amount AS DECIMAL(14,2)) >= CAST(? AS DECIMAL(14,2))', [$filters['amount_min']]))
            ->when(isset($filters['amount_max']), fn ($q) => $q->whereRaw('CAST(amount AS DECIMAL(14,2)) <= CAST(? AS DECIMAL(14,2))', [$filters['amount_max']]));
    }

    public function queue(Builder $query, string $section): Builder
    {
        return match ($section) {
            'tasks' => $query->where('actionable', 1),
            'budgets' => $query->where('type', 'budget'),
            'budget-review', 'releases', 'acknowledgment', 'liquidation-review' => $query->where('stage', $section),
            'returned' => $query->whereIn('status', ['Rejected', 'rejected', 'returned_for_revision']),
            'cash' => $query->where('type', 'ordinary'),
            'liquidations', 'liquidation-reports' => $query->whereIn('type', ['travel', 'ordinary']),
            'missing' => $query->where('missing', 1),
            'settlement' => $query->where('type', 'travel')->where('status', 'approved')->where(function ($q): void {
                $q->whereNotExists(fn ($s) => $s->selectRaw('1')->from('financial_settlements')->whereColumn('liquidation_id', 'records.id'))
                    ->orWhereExists(fn ($s) => $s->selectRaw('1')->from('financial_settlements')->whereColumn('liquidation_id', 'records.id')->where('outstanding_balance', '!=', 0));
            }),
            default => $query,
        };
    }

    /** @return array<string, int> */
    public function counters(User $user): array
    {
        $base = $this->records($user);
        $counts = [];
        foreach (['tasks', 'budgets', 'budget-review', 'releases', 'acknowledgment', 'liquidation-review', 'missing', 'settlement'] as $section) {
            $counts[$section] = $user->can(self::SECTIONS[$section][1]) ? $this->queue(clone $base, $section)->count() : 0;
        }

        return $counts;
    }

    /** Stored money only; each currency is kept separate. */
    public function payments(User $user, bool $thisMonth = false): Builder
    {
        Gate::forUser($user)->authorize('mi.financial-reports.view');

        return DB::table('budget_releases as r')->where('r.company_id', $user->currentCompany()->getKey())
            ->whereIn('r.budget_request_id', BudgetRequest::withTrashed()->where('company_id', $user->currentCompany()->getKey())->select('id'))
            ->when($thisMonth, fn ($q) => $q->whereBetween('r.released_at', [now()->startOfMonth(), now()->endOfMonth()]));
    }

    public static function recordUrl(object $row): string
    {
        return match ($row->type) {
            'budget' => route('accounting.mi.budgets.show', $row->id),
            'travel' => route('accounting.mi.travel.show', $row->id),
            default => route('accounting.mi.liquidation.show', $row->id),
        };
    }
}
