<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MmcPplImportBatch extends Model
{
    protected $fillable = [
        'company_id', 'created_by', 'original_file_name', 'file_sha256', 'storage_path',
        'file_size_bytes', 'status', 'attempt_count', 'summary', 'error_message', 'previewed_at', 'imported_at',
    ];

    protected $casts = [
        'summary' => 'array',
        'previewed_at' => 'datetime',
        'imported_at' => 'datetime',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(MmcPplImportRow::class);
    }

    public function sourceMappings(): HasMany
    {
        return $this->hasMany(MmcPplImportSourceMapping::class);
    }
}
