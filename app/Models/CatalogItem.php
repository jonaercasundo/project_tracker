<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CatalogItem extends Model
{
    public const DEFAULT_CODE_PREFIX = 'ITEM';

    protected $table = 'items';

    protected $fillable = [
        'code_prefix',
        'item_name',
        'description',
        'unit',
        'price',
        'supplier_price',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'supplier_price' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CatalogItem $item): void {
            $prefix = Str::upper(trim((string) $item->code_prefix));
            $prefix = $prefix === '' ? self::DEFAULT_CODE_PREFIX : $prefix;

            $item->code_prefix = $prefix;
            $lastItemId = self::query()
                ->whereNull('project_id')
                ->where('code_prefix', $prefix)
                ->orderByDesc('id')
                ->value('item_id');
            $number = 0;

            if (is_string($lastItemId) && preg_match('/^'.preg_quote($prefix, '/').'-([0-9]+)$/', $lastItemId, $matches)) {
                $number = (int) $matches[1];
            }

            do {
                $number++;
                $itemId = sprintf('%s-%04d', $prefix, $number);
            } while (self::query()->where('item_id', $itemId)->exists());

            $item->item_id = $itemId;
        });
    }
}
