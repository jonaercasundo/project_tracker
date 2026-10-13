<?php

namespace App\Services;

use App\Models\LogisticsIntegration;
use App\Models\LogisticsIntegrationAuditLog;
use App\Models\LogisticsIntegrationCredential;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LogisticsChannelService
{
    public function __construct(private LogisticsIntegrationRegistry $registry) {}

    /** @param array<string, mixed> $data @return array{integration: LogisticsIntegration,key_id:string,secret:string} */
    public function create(array $data, int $actorId, ?string $ipAddress): array
    {
        if (in_array($data['integration_id'], $this->registry->environmentIntegrationIds(), true)) {
            throw ValidationException::withMessages([
                'integration_id' => 'This ID is already configured in the environment. Adopt that integration to preserve its credentials and scope.',
            ]);
        }

        $secret = $this->generateSecret();
        $keyId = $this->newKeyId($data['integration_id']);
        $integration = DB::transaction(function () use ($data, $actorId, $ipAddress, $secret, $keyId): LogisticsIntegration {
            $integration = LogisticsIntegration::query()->create([
                'integration_id' => $data['integration_id'],
                'name' => $data['name'],
                'authentication_method' => 'hmac_sha256',
                'webhook_url' => $data['webhook_url'] ?: route('api.v1.logistics.webhooks.delivery-status'),
                'enabled_event_types' => $data['enabled_event_types'],
                'status' => $data['status'],
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->saveScopes($integration, $data);
            LogisticsIntegrationCredential::query()->create([
                'integration_id' => $integration->id,
                'key_id' => $keyId,
                'secret' => $secret,
                'status' => 'active',
                'created_by' => $actorId,
            ]);
            $this->audit($integration, $actorId, 'integration_created', null, null, [
                'project_ids' => array_map('intval', $data['project_ids']),
                'school_scope_count' => count($data['school_scopes'] ?? []),
            ], $ipAddress);

            return $integration;
        }, 3);

        return ['integration' => $integration, 'key_id' => $keyId, 'secret' => $secret];
    }

    /** @param array<string, mixed> $data */
    public function update(LogisticsIntegration $integration, array $data, int $actorId, ?string $ipAddress): void
    {
        DB::transaction(function () use ($integration, $data, $actorId, $ipAddress): void {
            $lockedIntegration = LogisticsIntegration::query()->lockForUpdate()->findOrFail($integration->id);
            $lockedIntegration->fill([
                'name' => $data['name'],
                'webhook_url' => $data['webhook_url'] ?: route('api.v1.logistics.webhooks.delivery-status'),
                'enabled_event_types' => $data['enabled_event_types'],
                'status' => $data['status'],
                'updated_by' => $actorId,
            ])->save();
            $this->saveScopes($lockedIntegration, $data);
            $this->audit($lockedIntegration, $actorId, 'integration_updated', null, null, [
                'project_ids' => array_map('intval', $data['project_ids']),
                'school_scope_count' => count($data['school_scopes'] ?? []),
                'status' => $data['status'],
            ], $ipAddress);
        }, 3);
    }

    /** @return array{key_id:string,secret:string,overlap_days:int} */
    public function rotate(LogisticsIntegration $integration, int $actorId, ?string $ipAddress, int $overlapDays = 7): array
    {
        $overlapDays = max(1, min($overlapDays, 30));
        $secret = $this->generateSecret();
        $keyId = $this->newKeyId($integration->integration_id);

        DB::transaction(function () use ($integration, $actorId, $ipAddress, $overlapDays, $secret, $keyId): void {
            $lockedIntegration = LogisticsIntegration::query()->lockForUpdate()->findOrFail($integration->id);
            LogisticsIntegrationCredential::query()
                ->where('integration_id', $lockedIntegration->id)
                ->where('status', 'active')
                ->whereNull('expires_at')
                ->update(['expires_at' => now()->addDays($overlapDays), 'updated_at' => now()]);
            LogisticsIntegrationCredential::query()->create([
                'integration_id' => $lockedIntegration->id,
                'key_id' => $keyId,
                'secret' => $secret,
                'status' => 'active',
                'created_by' => $actorId,
            ]);
            $this->audit($lockedIntegration, $actorId, 'credential_rotated', 'credential', $keyId, [
                'previous_credentials_expire_in_days' => $overlapDays,
            ], $ipAddress);
        }, 3);

        return ['key_id' => $keyId, 'secret' => $secret, 'overlap_days' => $overlapDays];
    }

    public function revokeCredential(LogisticsIntegration $integration, LogisticsIntegrationCredential $credential, int $actorId, ?string $ipAddress): void
    {
        abort_unless((int) $credential->integration_id === (int) $integration->id, 404);

        DB::transaction(function () use ($integration, $credential, $actorId, $ipAddress): void {
            $lockedCredential = LogisticsIntegrationCredential::query()->lockForUpdate()->findOrFail($credential->id);
            if ($lockedCredential->status === 'revoked') {
                return;
            }

            $lockedCredential->forceFill(['status' => 'revoked', 'revoked_at' => now()])->save();
            $this->audit($integration, $actorId, 'credential_revoked', 'credential', $lockedCredential->key_id, [], $ipAddress);
        }, 3);
    }

    /** @return array{integration: LogisticsIntegration, source: string}|null */
    public function adoptEnvironmentIntegration(string $integrationId, string $name, int $actorId, ?string $ipAddress): ?array
    {
        $keys = config('logistics-webhooks.keys', []);
        $legacyScopes = config('logistics-webhooks.integration_scopes.'.$integrationId, []);
        if (! is_array($keys) || ! isset($keys[$integrationId]) || ! is_string($keys[$integrationId]) || $keys[$integrationId] === '') {
            return null;
        }

        return DB::transaction(function () use ($integrationId, $name, $actorId, $ipAddress, $keys, $legacyScopes): array {
            $integration = LogisticsIntegration::query()->create([
                'integration_id' => $integrationId,
                'name' => $name,
                'authentication_method' => 'hmac_sha256',
                'webhook_url' => route('api.v1.logistics.webhooks.delivery-status'),
                'enabled_event_types' => config('logistics-webhooks.allowed_event_types', []),
                'status' => 'active',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $projectIds = array_map('intval', is_array($legacyScopes['project_ids'] ?? null) ? $legacyScopes['project_ids'] : []);
            foreach ($projectIds as $projectId) {
                DB::table('logistics_integration_projects')->insert([
                    'integration_id' => $integration->id,
                    'project_id' => $projectId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $legacySchoolIds = array_map('strval', is_array($legacyScopes['school_ids'] ?? null) ? $legacyScopes['school_ids'] : []);
            if ($legacySchoolIds !== []) {
                $schoolRows = DB::table('school')->whereIn('school_id', $legacySchoolIds)
                    ->whereIn('project_id', $projectIds)->get(['project_id', 'school_id']);
                if ($schoolRows->isEmpty()) {
                    throw ValidationException::withMessages(['integration' => 'Legacy school scope could not be mapped safely.']);
                }
                foreach ($schoolRows as $school) {
                    DB::table('logistics_integration_schools')->insert([
                        'integration_id' => $integration->id,
                        'project_id' => (int) $school->project_id,
                        'school_id' => (string) $school->school_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            LogisticsIntegrationCredential::query()->create([
                'integration_id' => $integration->id,
                'key_id' => $integrationId,
                'secret' => $keys[$integrationId],
                'status' => 'active',
                'created_by' => $actorId,
            ]);
            $this->audit($integration, $actorId, 'environment_integration_adopted', null, null, [
                'project_ids' => $projectIds,
                'school_scope_count' => count($legacySchoolIds),
                'secret_displayed' => false,
            ], $ipAddress);

            return ['integration' => $integration, 'source' => 'environment'];
        }, 3);
    }

    public function audit(
        ?LogisticsIntegration $integration,
        int $actorId,
        string $action,
        ?string $targetType,
        string|array|null $targetReferenceOrDetails,
        array $details = [],
        ?string $ipAddress = null
    ): void {
        if (is_array($targetReferenceOrDetails)) {
            $details = $targetReferenceOrDetails;
            $targetReference = null;
        } else {
            $targetReference = $targetReferenceOrDetails;
        }

        LogisticsIntegrationAuditLog::query()->create([
            'integration_id' => $integration?->id,
            'integration_reference' => $integration?->integration_id,
            'actor_user_id' => $actorId,
            'action' => $action,
            'target_type' => $targetType,
            'target_reference' => $targetReference,
            'details' => $details,
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $data */
    private function saveScopes(LogisticsIntegration $integration, array $data): void
    {
        $schoolScopes = $data['school_scopes'] ?? [];
        $allSchools = ($data['all_schools'] ?? false) === true || ($data['all_schools'] ?? null) === '1';
        if (($schoolScopes !== []) === $allSchools) {
            throw ValidationException::withMessages([
                'school_scopes' => 'Choose specific schools or explicitly allow all schools in the selected projects.',
            ]);
        }

        DB::table('logistics_integration_projects')->where('integration_id', $integration->id)->delete();
        DB::table('logistics_integration_schools')->where('integration_id', $integration->id)->delete();
        foreach (array_unique(array_map('intval', $data['project_ids'])) as $projectId) {
            DB::table('logistics_integration_projects')->insert([
                'integration_id' => $integration->id,
                'project_id' => $projectId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($schoolScopes as $scopeToken) {
            [$projectIdText, $schoolToken] = explode(':', $scopeToken, 2);
            $projectId = (int) $projectIdText;
            $schoolId = base64_decode(strtr($schoolToken, '-_', '+/'), true);
            if ($schoolId === false || ! in_array($projectId, array_map('intval', $data['project_ids']), true)
                || ! DB::table('school')->where('project_id', $projectId)->where('school_id', $schoolId)->exists()
            ) {
                throw ValidationException::withMessages(['school_scopes' => 'Choose schools that belong to an authorized project.']);
            }

            DB::table('logistics_integration_schools')->insert([
                'integration_id' => $integration->id,
                'project_id' => $projectId,
                'school_id' => $schoolId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function generateSecret(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    }

    private function newKeyId(string $integrationId): string
    {
        return substr($integrationId, 0, 48).'.'.Str::lower(Str::random(12));
    }
}
