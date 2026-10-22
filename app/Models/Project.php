<?php

namespace App\Models;

use App\Models\New\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Project extends Model
{
    protected $primaryKey = 'project_id';

    protected $table = 'projects';

    public $incrementing = true;

    public $timestamps = false;

    protected $keyType = 'int';

    protected $fillable = [
        'company_id',
        'project_name',
        'project_code',
        'ref_no',
        'agency',
        'contract_amount',
        'notice_of_award_date',
        'start_date',
        'keystage',
        'ABC',
        'status',
        'project_information_id',
        'end_date',
    ];

    protected $casts = [
        'notice_of_award_date' => 'date',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'contract_amount' => 'decimal:2',
    ];

    public function bidding(): BelongsTo
    {
        if (Schema::hasColumn($this->getTable(), 'project_information_id')) {
            return $this->belongsTo(ProjectInformation::class, 'project_information_id');
        }

        return $this->belongsTo(ProjectInformation::class, 'project_id', 'project_id');
    }

    public function arSetting()
    {
        return $this->hasOne(
            ARSetting::class,
            'project_id',
            'project_id'
        );
    }

    public function items()
    {
        return $this->hasMany(
            Item::class,
            'project_id',
            'project_id'
        );
    }

    public function lots()
    {
        return $this->hasMany(
            ProjectLot::class,
            'project_id',
            'project_id'
        );
    }
}
