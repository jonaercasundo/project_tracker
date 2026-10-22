<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MmcPplImportRow extends Model
{
    protected $fillable = [
        'mmc_ppl_import_batch_id', 'source_sheet', 'source_row', 'project_code', 'lot_number',
        'source_fingerprint', 'status', 'source_data', 'normalized_data', 'errors', 'warnings', 'financial_plan_id',
    ];

    protected $casts = [
        'source_data' => 'array',
        'normalized_data' => 'array',
        'errors' => 'array',
        'warnings' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(MmcPplImportBatch::class, 'mmc_ppl_import_batch_id');
    }

    public function financialPlan(): BelongsTo
    {
        return $this->belongsTo(ProjectFinancialPlan::class, 'financial_plan_id');
    }
}
