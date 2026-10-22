<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MmcPplImportSourceMapping extends Model
{
    protected $fillable = [
        'company_id', 'source_fingerprint', 'mmc_ppl_import_batch_id', 'mmc_ppl_import_row_id', 'financial_plan_id',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(MmcPplImportBatch::class, 'mmc_ppl_import_batch_id');
    }

    public function importRow(): BelongsTo
    {
        return $this->belongsTo(MmcPplImportRow::class, 'mmc_ppl_import_row_id');
    }

    public function financialPlan(): BelongsTo
    {
        return $this->belongsTo(ProjectFinancialPlan::class, 'financial_plan_id');
    }
}
