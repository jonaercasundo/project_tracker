<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class AiAssistantAttachment extends Model
{
    protected $fillable = [
        'conversation_id', 'user_id', 'company_id', 'disk', 'path', 'original_name', 'size_bytes', 'sha256', 'parsed_data', 'expires_at',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function getParsedDataAttribute(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        return json_decode(Crypt::decryptString($value), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @param array<string, mixed> $value */
    public function setParsedDataAttribute(array $value): void
    {
        $this->attributes['parsed_data'] = Crypt::encryptString(json_encode($value, JSON_THROW_ON_ERROR));
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }
}
