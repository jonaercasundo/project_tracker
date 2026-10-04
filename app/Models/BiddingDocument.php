<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiddingDocument extends Model
{
    protected $fillable = [
        'project_information_id', 'folder_id', 'display_name', 'original_name', 'stored_name',
        'storage_disk', 'storage_path', 'mime_type', 'extension', 'file_size', 'description', 'uploaded_by', 'version', 'uploaded_at',
    ];

    protected $hidden = ['stored_name', 'storage_disk', 'storage_path'];

    protected function casts(): array
    {
        return ['file_size' => 'integer', 'version' => 'integer', 'uploaded_at' => 'datetime'];
    }

    public function bidding(): BelongsTo
    {
        return $this->belongsTo(ProjectInformation::class, 'project_information_id');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(BiddingDocumentFolder::class, 'folder_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'user_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(BiddingDocumentVersion::class);
    }
}
