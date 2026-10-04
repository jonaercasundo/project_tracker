<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiddingDocumentVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'bidding_document_id', 'version', 'original_name', 'stored_name', 'storage_disk', 'storage_path',
        'mime_type', 'extension', 'file_size', 'uploaded_by', 'created_at',
    ];

    protected $hidden = ['stored_name', 'storage_disk', 'storage_path'];

    protected function casts(): array
    {
        return ['version' => 'integer', 'file_size' => 'integer', 'created_at' => 'datetime'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(BiddingDocument::class, 'bidding_document_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'user_id');
    }
}
