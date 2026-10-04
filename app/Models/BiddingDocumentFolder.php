<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BiddingDocumentFolder extends Model
{
    protected $fillable = ['project_information_id', 'parent_id', 'name', 'storage_uuid', 'created_by'];

    protected $hidden = ['storage_uuid'];

    protected static function booted(): void
    {
        static::creating(function (self $folder): void {
            $folder->storage_uuid ??= (string) Str::uuid();
        });
    }

    public function bidding(): BelongsTo
    {
        return $this->belongsTo(ProjectInformation::class, 'project_information_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BiddingDocument::class, 'folder_id');
    }
}
