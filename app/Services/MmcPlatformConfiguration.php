<?php

namespace App\Services;

use App\Models\MmcPlatformSetting;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MmcPlatformConfiguration
{
    /** @return array{base_url: string, read_key: string, credential_source: string, has_saved_key: bool, storage_available: bool, credential_error: ?string} */
    public function current(): array
    {
        $setting = null;
        $credentialError = null;
        $storageAvailable = Schema::hasTable('mmc_platform_settings');

        if ($storageAvailable) {
            try {
                $setting = MmcPlatformSetting::query()->find(1);
            } catch (Throwable) {
                $credentialError = 'The saved MMC API key could not be decrypted. Check that APP_KEY matches the key used when it was saved.';
            }
        }

        $databaseKey = null;
        if ($setting !== null) {
            try {
                $databaseKey = $setting->read_key;
            } catch (Throwable) {
                $credentialError = 'The saved MMC API key could not be decrypted. Check that APP_KEY matches the key used when it was saved.';
            }
        }

        $environmentKey = trim((string) config('services.mmc_platform.read_key'));
        $readKey = is_string($databaseKey) && trim($databaseKey) !== '' ? $databaseKey : $environmentKey;

        return [
            'base_url' => (string) ($setting?->base_url ?: config('services.mmc_platform.base_url', '')),
            'read_key' => $readKey,
            'credential_source' => is_string($databaseKey) && trim($databaseKey) !== '' ? 'database' : ($environmentKey !== '' ? 'environment' : 'none'),
            'has_saved_key' => is_string($databaseKey) && trim($databaseKey) !== '',
            'storage_available' => $storageAvailable,
            'credential_error' => $credentialError,
        ];
    }
}
