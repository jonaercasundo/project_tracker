<?php

namespace App\Services;

use App\Exceptions\GoogleSheetsApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use OpenSSLAsymmetricKey;
use Throwable;

class GoogleSheetsApiClient
{
    public function __construct(private GoogleSheetsBillingConfiguration $configuration) {}

    /** @return array{configured: bool, reason: ?string} */
    public function configurationStatus(): array
    {
        $settings = $this->configuration->current();
        if (! $settings['enabled']) {
            return ['configured' => false, 'reason' => 'Google Sheets billing sync is disabled by configuration.'];
        }

        if (trim($settings['spreadsheet_id']) === '') {
            return ['configured' => false, 'reason' => 'Spreadsheet ID is not configured.'];
        }

        if ($settings['credential_error'] !== null) {
            return ['configured' => false, 'reason' => $settings['credential_error']];
        }

        if (! is_string($settings['credential_json']) || trim($settings['credential_json']) === '') {
            return ['configured' => false, 'reason' => 'Service account credentials are not configured. Save credentials or configure GOOGLE_SHEETS_SERVICE_ACCOUNT_JSON as a fallback.'];
        }

        $credentials = $this->configuration->credentials();
        if ($credentials === null) {
            return ['configured' => false, 'reason' => 'Service account credential content is not valid JSON.'];
        }

        if (! is_string($credentials['client_email'] ?? null) || ! filter_var($credentials['client_email'], FILTER_VALIDATE_EMAIL)
            || ! is_string($credentials['private_key'] ?? null)) {
            return ['configured' => false, 'reason' => 'Service account credentials must include a valid client_email and private_key.'];
        }

        if (! extension_loaded('openssl')) {
            return ['configured' => false, 'reason' => 'The PHP OpenSSL extension is required to authenticate the service account.'];
        }

        if (! (openssl_pkey_get_private($credentials['private_key']) instanceof OpenSSLAsymmetricKey)) {
            return ['configured' => false, 'reason' => 'Configured service account private key is invalid or unsupported.'];
        }

        return ['configured' => true, 'reason' => null];
    }

    /** @return array<int, array<int, mixed>> */
    public function values(string $range, ?string $spreadsheetId = null): array
    {
        $spreadsheetId ??= $this->configuration->current()['spreadsheet_id'];
        $url = rtrim((string) config('google_sheets.billing.api_base_url'), '/').'/'.rawurlencode($spreadsheetId).'/values/'.rawurlencode($range);
        $response = $this->request()->get($url, ['valueRenderOption' => 'FORMATTED_VALUE']);
        $this->throwForFailedResponse($response->status(), $response->json('error.message'));

        $values = $response->json('values', []);
        if (! is_array($values)) {
            throw new GoogleSheetsApiException('Google Sheets returned an invalid values response.');
        }

        return $values;
    }

    public function updateCell(string $range, string $value, ?string $spreadsheetId = null): void
    {
        $spreadsheetId ??= $this->configuration->current()['spreadsheet_id'];
        $url = rtrim((string) config('google_sheets.billing.api_base_url'), '/').'/'.rawurlencode($spreadsheetId).'/values/'.rawurlencode($range).'?valueInputOption=RAW';
        $response = $this->request()->put($url, [
            'range' => $range,
            'majorDimension' => 'ROWS',
            'values' => [[$value]],
        ]);
        $this->throwForFailedResponse($response->status(), $response->json('error.message'));
    }

    /** @return array{status: string, message: string} */
    public function testConnection(): array
    {
        $status = $this->configurationStatus();
        if (! $status['configured']) {
            return ['status' => 'configuration_required', 'message' => $status['reason'] ?? 'Google Sheets is not configured.'];
        }

        $settings = $this->configuration->current();
        $range = "'".str_replace("'", "''", $settings['sheet_tab'])."'!A1";

        try {
            $this->values($range, $settings['spreadsheet_id']);
        } catch (GoogleSheetsApiException $exception) {
            return ['status' => 'failed', 'message' => $this->safeFailureMessage($exception)];
        } catch (Throwable) {
            return ['status' => 'failed', 'message' => 'Google Sheets connection test failed due to a network or application error.'];
        }

        return ['status' => 'success', 'message' => 'The configured account can access the spreadsheet and worksheet.'];
    }

    private function request(): PendingRequest
    {
        $status = $this->configurationStatus();
        if (! $status['configured']) {
            throw new GoogleSheetsApiException($status['reason'] ?? 'Google Sheets is not configured.');
        }

        return Http::withToken($this->accessToken())->acceptJson()->timeout(20)->connectTimeout(10);
    }

    private function accessToken(): string
    {
        $credentials = $this->configuration->credentials();
        if ($credentials === null) {
            throw new GoogleSheetsApiException('Google service account credentials are unavailable.');
        }

        $cacheKey = $this->accessTokenCacheKey($credentials);
        $cachedToken = Cache::get($cacheKey);
        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $privateKey = openssl_pkey_get_private($credentials['private_key']);
        if (! $privateKey instanceof OpenSSLAsymmetricKey) {
            throw new GoogleSheetsApiException('The configured service account private key could not be loaded.');
        }

        $issuedAt = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => config('google_sheets.billing.scope'),
            'aud' => config('google_sheets.billing.oauth_token_url'),
            'iat' => $issuedAt,
            'exp' => $issuedAt + 3600,
        ], JSON_THROW_ON_ERROR));
        $unsignedJwt = $header.'.'.$claims;
        if (! openssl_sign($unsignedJwt, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new GoogleSheetsApiException('Unable to sign a Google service account assertion.');
        }

        try {
            $response = Http::asForm()->acceptJson()->timeout(15)->connectTimeout(10)->post(
                (string) config('google_sheets.billing.oauth_token_url'),
                [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $unsignedJwt.'.'.$this->base64UrlEncode($signature),
                ]
            );
        } catch (ConnectionException) {
            throw new GoogleSheetsApiException('Google OAuth token request failed due to a network error.');
        }

        if (! $response->successful() || ! is_string($response->json('access_token'))) {
            throw new GoogleSheetsApiException('Google OAuth token request was rejected (HTTP '.$response->status().').', $response->status());
        }

        $token = $response->json('access_token');
        Cache::put($cacheKey, $token, now()->addSeconds(max(60, (int) $response->json('expires_in', 3600) - 60)));

        return $token;
    }

    private function throwForFailedResponse(int $statusCode, mixed $apiMessage): void
    {
        if (in_array($statusCode, [200, 201], true)) {
            return;
        }

        if ($statusCode === 401) {
            $credentials = $this->configuration->credentials();
            if ($credentials !== null) {
                Cache::forget($this->accessTokenCacheKey($credentials));
            }
        }

        $detail = is_string($apiMessage) && $apiMessage !== '' ? ' '.mb_substr($apiMessage, 0, 500) : '';
        throw new GoogleSheetsApiException('Google Sheets API request failed (HTTP '.$statusCode.').'.$detail, $statusCode);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /** @param array<string, mixed> $credentials */
    private function accessTokenCacheKey(array $credentials): string
    {
        return 'google_sheets_access_token_'.hash('sha256', (string) $credentials['client_email'].'|'.hash('sha256', (string) $credentials['private_key']));
    }

    private function safeFailureMessage(GoogleSheetsApiException $exception): string
    {
        return match ($exception->statusCode) {
            401 => 'Google rejected the service account credentials. Check whether the key is valid or revoked.',
            403 => 'Google denied spreadsheet access. Share the spreadsheet with the service account as an Editor.',
            404 => 'The spreadsheet or worksheet was not found. Verify the ID and exact worksheet name.',
            429 => 'Google Sheets rate limited the connection test. Try again later.',
            null => 'Google Sheets could not be reached. Check server network access and try again.',
            default => 'Google Sheets connection test failed (HTTP '.$exception->statusCode.').',
        };
    }
}
