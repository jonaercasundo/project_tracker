<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFinancialPlanCostItem extends Model
{
    protected $fillable = [
        'project_financial_plan_version_id',
        'category',
        'label',
        'amount',
        'calculation_basis',
        'rate',
        'source_type',
        'source_reference',
        'source_snapshot',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'rate' => 'decimal:6',
        'source_snapshot' => 'array',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(ProjectFinancialPlanVersion::class, 'project_financial_plan_version_id');
    }
}
