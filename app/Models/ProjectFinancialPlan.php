<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectFinancialPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'project_id',
        'project_information_id',
        'lot_id',
        'ppl_form_id',
        'mmc_ppl_source_link_id',
        'revenue_year',
        'name',
        'status',
        'created_by',
        'updated_by',
    ];

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function bidding(): BelongsTo
    {
        return $this->belongsTo(ProjectInformation::class, 'project_information_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ProjectLot::class, 'lot_id');
    }

    public function pplForm(): BelongsTo
    {
        return $this->belongsTo(PplForm::class, 'ppl_form_id');
    }

    public function mmcPplSourceLink(): BelongsTo
    {
        return $this->belongsTo(MmcPplSourceLink::class, 'mmc_ppl_source_link_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ProjectFinancialPlanVersion::class)->orderByDesc('version_number');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(ProjectFinancialPlanVersion::class)->ofMany('version_number', 'max');
    }
}
