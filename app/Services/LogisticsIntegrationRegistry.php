<?php

namespace App\Services;

use App\Models\LogisticsIntegration;
use App\Models\LogisticsIntegrationCredential;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LogisticsIntegrationRegistry
{
    /** @return array{integration_id: string, secret: string, source: string, enabled: bool}|null */
    public function credential(string $keyId): ?array
    {
        if (! Schema::hasTable('logistics_integrations')) {
            return $this->environmentCredential($keyId);
        }

        if (! Schema::hasTable('logistics_integration_credentials')) {
            if (LogisticsIntegration::query()->where('integration_id', $keyId)->exists()) {
                return ['integration_id' => $keyId, 'secret' => '', 'source' => 'database', 'enabled' => false];
            }

            return $this->environmentCredential($keyId);
        }

        $credential = LogisticsIntegrationCredential::query()->where('key_id', $keyId)->first();

        if ($credential !== null) {
            $integration = LogisticsIntegration::query()->find($credential->integration_id);
            $enabled = $integration?->status === 'active'
                && $credential->status === 'active'
                && ($credential->expires_at === null || $credential->expires_at->isFuture());

            try {
                $secret = (string) $credential->secret;
            } catch (Throwable) {
                $secret = '';
                $enabled = false;
            }

            return [
                'integration_id' => (string) ($integration?->integration_id ?? ''),
                'secret' => $secret,
                'source' => 'database',
                'enabled' => $enabled,
            ];
        }

        // A managed integration owns its key namespace: never fall back to .env
        // after it has been adopted, disabled, or had its credentials revoked.
        if (LogisticsIntegration::query()->where('integration_id', $keyId)->exists()) {
            return ['integration_id' => $keyId, 'secret' => '', 'source' => 'database', 'enabled' => false];
        }

        return $this->environmentCredential($keyId);
    }

    /** @return array{project_ids: array<int, int>, school_ids: array<int, string>, school_scopes: array<int, array{project_id:int,school_id:string}>} */
    public function scope(string $integrationId): array
    {
        $integration = Schema::hasTable('logistics_integrations')
            && Schema::hasTable('logistics_integration_projects')
            && Schema::hasTable('logistics_integration_schools')
            ? LogisticsIntegration::query()->where('integration_id', $integrationId)->first()
            : null;

        if ($integration !== null) {
            $projectIds = DB::table('logistics_integration_projects')
                ->where('integration_id', $integration->id)
                ->orderBy('project_id')
                ->pluck('project_id')
                ->map(fn ($projectId): int => (int) $projectId)
                ->all();
            $schoolScopes = DB::table('logistics_integration_schools')
                ->where('integration_id', $integration->id)
                ->orderBy('project_id')
                ->orderBy('school_id')
                ->get(['project_id', 'school_id'])
                ->map(fn (object $row): array => ['project_id' => (int) $row->project_id, 'school_id' => (string) $row->school_id])
                ->all();

            return ['project_ids' => $projectIds, 'school_ids' => [], 'school_scopes' => $schoolScopes];
        }

        $legacyScope = config('logistics-webhooks.integration_scopes.'.$integrationId, []);

        return [
            'project_ids' => array_map('intval', is_array($legacyScope['project_ids'] ?? null) ? $legacyScope['project_ids'] : []),
            'school_ids' => array_map('strval', is_array($legacyScope['school_ids'] ?? null) ? $legacyScope['school_ids'] : []),
            'school_scopes' => [],
        ];
    }

    /** @return array<int, string> */
    public function enabledEventTypes(string $integrationId): array
    {
        $integration = Schema::hasTable('logistics_integrations')
            ? LogisticsIntegration::query()->where('integration_id', $integrationId)->first()
            : null;

        if ($integration !== null) {
            return $integration->status === 'active' ? ($integration->enabled_event_types ?? []) : [];
        }

        return config('logistics-webhooks.allowed_event_types', []);
    }

    public function isActive(string $integrationId): bool
    {
        $integration = Schema::hasTable('logistics_integrations')
            ? LogisticsIntegration::query()->where('integration_id', $integrationId)->first()
            : null;

        if ($integration !== null) {
            return $integration->status === 'active';
        }

        $legacyKeys = config('logistics-webhooks.keys', []);

        return is_array($legacyKeys) && isset($legacyKeys[$integrationId]) && is_string($legacyKeys[$integrationId]) && $legacyKeys[$integrationId] !== '';
    }

    public function markCredentialUsed(string $keyId): void
    {
        if (! Schema::hasTable('logistics_integration_credentials')) {
            return;
        }

        LogisticsIntegrationCredential::query()->where('key_id', $keyId)
            ->where('status', 'active')
            ->update(['last_used_at' => now(), 'updated_at' => now()]);
    }

    /** @return array<int, string> */
    public function environmentIntegrationIds(): array
    {
        $keys = config('logistics-webhooks.keys', []);

        if (! is_array($keys)) {
            return [];
        }

        return array_values(array_filter(array_keys($keys), fn ($key): bool => is_string($key)
            && isset($keys[$key]) && is_string($keys[$key]) && $keys[$key] !== ''));
    }

    /** @return array{integration_id:string,secret:string,source:string,enabled:bool}|null */
    private function environmentCredential(string $keyId): ?array
    {
        $legacyKeys = config('logistics-webhooks.keys', []);
        if (! is_array($legacyKeys) || ! isset($legacyKeys[$keyId]) || ! is_string($legacyKeys[$keyId])) {
            return null;
        }

        return [
            'integration_id' => $keyId,
            'secret' => $legacyKeys[$keyId],
            'source' => 'environment',
            'enabled' => $legacyKeys[$keyId] !== '',
        ];
    }
}
