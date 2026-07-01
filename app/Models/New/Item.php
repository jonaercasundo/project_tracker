<?php

namespace App\Models\New;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $table = 'items';

    protected $fillable = [
        'item_id',
        'code_prefix',
        'item_name',
        'description',
        'project_id',
        'unit',
        'price',
        'supplier_price',
        'active',
    ];

    protected static function booted()
    {
        static::creating(function ($item) {

            // Skip if item_id was manually supplied
            if (!empty($item->item_id)) {
                return;
            }

            $prefix = strtoupper(trim($item->code_prefix));

            if (empty($prefix)) {
                throw new \Exception('Code prefix is required.');
            }

            // Find the latest item with the same prefix
            $latest = self::where('code_prefix', $prefix)
                ->orderByDesc('id')
                ->first();

            if ($latest) {

                $number = (int) substr($latest->item_id, strlen($prefix) + 1);

                $number++;

            } else {

                $number = 1;

            }

            $item->item_id = sprintf('%s-%04d', $prefix, $number);

        });
    }

    public function project()
    {
        return $this->belongsTo(\App\Models\Project::class, 'project_id', 'id');
    }
}