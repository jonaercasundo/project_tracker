<?php

namespace App\Services;

use App\Models\AiActivity;
use App\Models\AiProviderSetting;
use Illuminate\Support\Facades\Schema;

class AiProviderConfiguration
{
    public function setting(): ?AiProviderSetting
    {
        if (! Schema::hasTable('ai_provider_settings')) {
            return null;
        }

        return AiProviderSetting::query()->find(1);
    }

    public function apiKey(): ?string
    {
        $stored = $this->setting()?->api_key;
        if (is_string($stored) && trim($stored) !== '') {
            return trim($stored);
        }

        $fallback = config('services.openai.key');

        return is_string($fallback) && trim($fallback) !== '' ? trim($fallback) : null;
    }

    public function credentialSource(): string
    {
        if ($this->hasDatabaseKey()) {
            return 'database';
        }

        return $this->apiKey() !== null ? 'environment' : 'none';
    }

    public function hasDatabaseKey(): bool
    {
        $setting = $this->setting();

        return $setting !== null && is_string($setting->getRawOriginal('api_key')) && $setting->getRawOriginal('api_key') !== '';
    }

    public function model(): string
    {
        return $this->setting()?->model ?: (string) config('services.openai.assistant_model', 'gpt-5.5');
    }

    public function supportedModels(): array
    {
        return config('ai_assistant.supported_models', ['gpt-5.5']);
    }

    public function isEnabled(): bool
    {
        return $this->setting()?->enabled ?? (bool) config('ai_assistant.enabled', true);
    }

    public function perUserRequestLimit(): int
    {
        return max(1, $this->setting()?->per_user_requests_per_minute
            ?? (int) config('ai_assistant.default_per_user_requests_per_minute', 12));
    }

    public function monthlyTokenLimit(): ?int
    {
        $setting = $this->setting();

        return $setting !== null ? $setting->monthly_token_limit : config('ai_assistant.default_monthly_token_limit');
    }

    public function monthlyTokenUsage(): int
    {
        if (! Schema::hasTable('ai_activity_logs')) {
            return 0;
        }

        return (int) AiActivity::query()->where('status', 'completed')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('COALESCE(SUM(COALESCE(input_tokens, 0) + COALESCE(output_tokens, 0)), 0) as total_tokens')
            ->value('total_tokens');
    }

    public function monthlyLimitReached(): bool
    {
        $limit = $this->monthlyTokenLimit();

        return $limit !== null && $limit > 0 && $this->monthlyTokenUsage() >= $limit;
    }
}
