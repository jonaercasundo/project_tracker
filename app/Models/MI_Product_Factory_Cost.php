<?php

namespace App\Models;

use Database\Factories\MIProductFactoryCostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MI_Product_Factory_Cost extends Model
{
    use HasFactory;

    protected $table = 'mi_product_factory_costs';

    protected $fillable = [
        'factory_name',
        'factory_cost',
        'currency',
        'effective_date',
        'remarks',
        'is_primary',
    ];

    protected $hidden = ['primary_product_id'];

    protected $casts = [
        'factory_cost' => 'decimal:2',
        'effective_date' => 'date',
        'is_primary' => 'boolean',
    ];

    protected static function newFactory(): MIProductFactoryCostFactory
    {
        return MIProductFactoryCostFactory::new();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MI_Product::class, 'product_id', 'product_id');
    }
}
