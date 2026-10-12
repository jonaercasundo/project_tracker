<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiddingPackage extends Model
{
    protected $fillable = ['key', 'keystage_id', 'code', 'grade_alias', 'quantity_basis', 'cartons', 'cbm', 'volumetric_weight', 'gross_weight'];

    public function keyStage(): BelongsTo
    {
        return $this->belongsTo(BiddingKeyStage::class, 'keystage_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(BiddingSchoolAssignment::class, 'package_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProjectItem::class, 'bidding_package_id');
    }
}
