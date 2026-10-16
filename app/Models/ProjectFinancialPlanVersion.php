<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectFinancialPlanVersion extends Model
{
    protected $fillable = [
        'project_financial_plan_id',
        'version_number',
        'status',
        'change_summary',
        'input_data',
        'calculation_results',
        'assumptions_snapshot',
        'factory_cost_snapshot',
        'created_by',
    ];

    protected $casts = [
        'input_data' => 'array',
        'calculation_results' => 'array',
        'assumptions_snapshot' => 'array',
        'factory_cost_snapshot' => 'array',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ProjectFinancialPlan::class, 'project_financial_plan_id');
    }

    public function costItems(): HasMany
    {
        return $this->hasMany(ProjectFinancialPlanCostItem::class, 'project_financial_plan_version_id');
    }
}
