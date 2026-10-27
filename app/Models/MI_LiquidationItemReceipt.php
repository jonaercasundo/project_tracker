<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MI_LiquidationItemReceipt extends Model
{
    protected $table = 'mi_liquidation_item_receipts';

    protected $fillable = [
        'liquidation_item_id',
        'original_filename',
        'storage_path',
        'mime_type',
        'file_size',
        'uploaded_by',
        'ocr_reference',
    ];

    protected $hidden = ['storage_path'];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(MI_LiquidationItem::class, 'liquidation_item_id');
    }
}
