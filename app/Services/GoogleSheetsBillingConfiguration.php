<?php

namespace App\Services;

use App\Models\GoogleSheetsSetting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class GoogleSheetsBillingConfiguration
{
    /** @return array{enabled: bool, spreadsheet_id: string, sheet_tab: string, credential_json: ?string, credential_source: string, credential_error: ?string, has_saved_credentials: bool, has_saved_settings: bool} */
    public function current(): array
    {
        $setting = Schema::hasTable('google_sheets_settings')
            ? GoogleSheetsSetting::query()->find(1)
            : null;
        $hasSavedCredentials = is_string($setting?->getRawOriginal('service_account_json'))
            && $setting->getRawOriginal('service_account_json') !== '';
        $hasEnvironmentCredentialPath = is_string(config('google_sheets.billing.service_account_json'))
            && trim((string) config('google_sheets.billing.service_account_json')) !== '';
        $credentialJson = null;
        $credentialError = null;
        if ($hasSavedCredentials) {
            try {
                $credentialJson = $setting->service_account_json;
            } catch (Throwable) {
                $credentialError = 'Saved service account credentials cannot be decrypted. Confirm that APP_KEY matches the key used when these settings were saved.';
            }
        } else {
            [$credentialJson, $credentialError] = $this->environmentCredentialJson();
        }

        return [
            'enabled' => $setting?->enabled ?? (bool) config('google_sheets.billing.enabled', true),
            'spreadsheet_id' => $setting?->spreadsheet_id ?? (string) config('google_sheets.billing.spreadsheet_id', ''),
            'sheet_tab' => $setting?->sheet_tab ?? (string) config('google_sheets.billing.sheet_tab', 'Copy of SME LOT 2,9,11'),
            'credential_json' => $credentialJson,
            'credential_source' => $hasSavedCredentials ? 'database' : ($hasEnvironmentCredentialPath ? 'environment' : 'none'),
            'credential_error' => $credentialError,
            'has_saved_credentials' => $hasSavedCredentials,
            'has_saved_settings' => $setting !== null,
        ];
    }

    /** @return array<string, mixed>|null */
    public function credentials(): ?array
    {
        $credentialJson = $this->current()['credential_json'];
        if (! is_string($credentialJson) || trim($credentialJson) === '') {
            return null;
        }

        try {
            $credentials = json_decode($credentialJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        return is_array($credentials) ? $credentials : null;
    }

    /** @return array{?string, ?string} */
    private function environmentCredentialJson(): array
    {
        $credentialsPath = config('google_sheets.billing.service_account_json');
        if (! is_string($credentialsPath) || trim($credentialsPath) === '') {
            return [null, null];
        }

        if (! File::isFile($credentialsPath) || ! File::isReadable($credentialsPath)) {
            return [null, 'Configured environment service account credential file is missing or unreadable.'];
        }

        try {
            return [File::get($credentialsPath), null];
        } catch (Throwable) {
            return [null, 'Configured environment service account credential file could not be read.'];
        }
    }
}
