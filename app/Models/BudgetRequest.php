<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'control_id', 'employee_id', 'department', 'objectives',
        'travel_date_from', 'travel_date_to', 'place', 'country',
        'budget_cash', 'budget_credit_card', 'budget_travel_agent', 'budget_total',
        'status', 'approved_by', 'approved_at', 'noted_by', 'noted_at',
        'released_by', 'released_at', 'received_at', 'remarks',
    ];

    protected $casts = [
        'travel_date_from' => 'date',
        'travel_date_to' => 'date',
        'approved_at' => 'datetime',
        'noted_at' => 'datetime',
        'released_at' => 'datetime',
        'received_at' => 'datetime',
        'budget_cash' => 'decimal:2',
        'budget_credit_card' => 'decimal:2',
        'budget_travel_agent' => 'decimal:2',
        'budget_total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (BudgetRequest $model) {
            if (empty($model->control_id)) {
                $model->control_id = static::generateControlId();
            }
        });
    }

    public static function generateControlId(): string
    {
        $year = now()->year;
        $lastSeq = static::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->value('control_id');

        $next = 1;
        if ($lastSeq && preg_match('/(\d{4})$/', $lastSeq, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return sprintf('BR-%d-%04d', $year, $next);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function items()
    {
        return $this->hasMany(BudgetRequestItem::class);
    }

    public function liquidation()
    {
        return $this->hasOne(Liquidation::class);
    }

    /** Recompute cached totals from line items. Call after any item change. */
    public function recalcTotals(): void
    {
        $sums = $this->items()->selectRaw('
            COALESCE(SUM(budget_cash),0) as cash,
            COALESCE(SUM(budget_credit_card),0) as cc,
            COALESCE(SUM(budget_travel_agent),0) as agent,
            COALESCE(SUM(budget_total),0) as total
        ')->first();

        $this->update([
            'budget_cash' => $sums->cash,
            'budget_credit_card' => $sums->cc,
            'budget_travel_agent' => $sums->agent,
            'budget_total' => $sums->total,
        ]);
    }

    // --- Workflow transitions (called from BudgetRequestController) ---

    public function approve(User $approver): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    public function noteByAccounting(User $accountant): void
    {
        $this->update([
            'noted_by' => $accountant->id,
            'noted_at' => now(),
        ]);
    }

    public function release(User $releaser): void
    {
        $this->update([
            'status' => 'released',
            'released_by' => $releaser->id,
            'released_at' => now(),
        ]);
    }

    public function markReceived(): void
    {
        $this->update([
            'status' => 'in_progress',
            'received_at' => now(),
        ]);
    }
}
