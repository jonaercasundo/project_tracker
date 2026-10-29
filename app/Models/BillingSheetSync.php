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
            'destination_cells' => 'array',
            'validation_errors' => 'array',
            'written_amount' => 'decimal:2',
            'billing_batch_id' => 'integer',
            'authenticated_user_id' => 'integer',
            'sync_version' => 'integer',
        ];
    }
}
