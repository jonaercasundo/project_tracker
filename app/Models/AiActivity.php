<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiActivity extends Model
{
    protected $table = 'ai_activity_logs';

    protected $fillable = ['user_id', 'company_id', 'conversation_id', 'model', 'request_id', 'input_tokens', 'output_tokens', 'status'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }
}
