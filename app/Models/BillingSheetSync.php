<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingSheetSync extends Model
{
    protected $table = 'billing_sheet_syncs';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'transition_at' => 'datetime',
            'transition_date' => 'date',
            'synced_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
