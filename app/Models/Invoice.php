<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $table = 'invoices';

    protected $primaryKey = 'invoice_id';

    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'invoice_no',
        'amount',
        'invoice_date',
        'status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'invoice_date' => 'date',
            'payment_date' => 'date',
        ];
    }

    public function billingRecords(): HasMany
    {
        return $this->hasMany(DeliveryBillingRecord::class, 'invoice_id', 'invoice_id');
    }
}
