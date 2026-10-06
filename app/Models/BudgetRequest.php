<?php

namespace App\Models;

use App\Services\MiFinancialAmount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class BudgetRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
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
        $lastSeq = static::withTrashed()->whereYear('created_at', $year)
            ->orderByDesc('id')
            ->value('control_id');

        $next = 1;
        if ($lastSeq && preg_match('/(\d+)$/', $lastSeq, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return sprintf('BR-%d-%04d', $year, $next);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BudgetRequestItem::class);
    }

    public function liquidation(): HasOne
    {
        return $this->hasOne(Liquidation::class);
    }

    /** Recompute cached totals from line items. Call after any item change. */
    public function recalcTotals(): void
    {
        $items = $this->items()->get();
        $this->update([
            'budget_cash' => MiFinancialAmount::sum($items->pluck('budget_cash')),
            'budget_credit_card' => MiFinancialAmount::sum($items->pluck('budget_credit_card')),
            'budget_travel_agent' => MiFinancialAmount::sum($items->pluck('budget_travel_agent')),
            'budget_total' => MiFinancialAmount::sum($items->pluck('budget_total')),
        ]);
    }

    // --- Workflow transitions (called from BudgetRequestController) ---

    public function approve(User $approver): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approver->getKey(),
            'approved_at' => now(),
        ]);
    }

    public function noteByAccounting(User $accountant): void
    {
        $this->update([
            'noted_by' => $accountant->getKey(),
            'noted_at' => now(),
        ]);
    }

    public function release(User $releaser): void
    {
        $this->update([
            'status' => 'released',
            'released_by' => $releaser->getKey(),
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(FinancialActivity::class, 'record_id')->where('record_type', $this->getTable())->orderBy('id');
    }

    public function releases(): HasMany
    {
        return $this->hasMany(BudgetRelease::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function accountant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'noted_by');
    }

    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }
}
