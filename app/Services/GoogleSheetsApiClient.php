<?php

namespace App\Services;

use App\Exceptions\GoogleSheetsApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use OpenSSLAsymmetricKey;
use Throwable;

class GoogleSheetsApiClient
{
    /** @return array{configured: bool, reason: ?string} */
    public function configurationStatus(): array
    {
        if (! config('google_sheets.billing.enabled')) {
            return ['configured' => false, 'reason' => 'Google Sheets billing sync is disabled by configuration.'];
        }

        if (! is_string(config('google_sheets.billing.spreadsheet_id')) || trim((string) config('google_sheets.billing.spreadsheet_id')) === '') {
            return ['configured' => false, 'reason' => 'Spreadsheet ID is not configured.'];
        }

        $credentialsPath = config('google_sheets.billing.service_account_json');
        if (! is_string($credentialsPath) || trim($credentialsPath) === '') {
            return ['configured' => false, 'reason' => 'Service account credential file is not configured (GOOGLE_SHEETS_SERVICE_ACCOUNT_JSON).'];
        }

        if (! File::isFile($credentialsPath) || ! File::isReadable($credentialsPath)) {
            return ['configured' => false, 'reason' => 'Configured Google service account credential file is missing or unreadable.'];
        }

        try {
            $credentials = json_decode(File::get($credentialsPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return ['configured' => false, 'reason' => 'Google service account credential file is not valid JSON.'];
        }

        if (! is_array($credentials) || ! is_string($credentials['client_email'] ?? null) || ! is_string($credentials['private_key'] ?? null)) {
            return ['configured' => false, 'reason' => 'Service account credentials must include client_email and private_key.'];
        }

        if (! (openssl_pkey_get_private($credentials['private_key']) instanceof OpenSSLAsymmetricKey)) {
            return ['configured' => false, 'reason' => 'Configured service account private key is invalid or unsupported.'];
        }

        return ['configured' => true, 'reason' => null];
    }

    /** @return array<int, array<int, mixed>> */
    public function values(string $range): array
    {
        $spreadsheetId = (string) config('google_sheets.billing.spreadsheet_id');
        $url = rtrim((string) config('google_sheets.billing.api_base_url'), '/').'/'.rawurlencode($spreadsheetId).'/values/'.rawurlencode($range);
        $response = $this->request()->get($url, ['valueRenderOption' => 'FORMATTED_VALUE']);
        $this->throwForFailedResponse($response->status(), $response->json('error.message'));

        $values = $response->json('values', []);
        if (! is_array($values)) {
            throw new GoogleSheetsApiException('Google Sheets returned an invalid values response.');
        }

        return $values;
    }

    public function updateCell(string $range, string $value): void
    {
        $spreadsheetId = (string) config('google_sheets.billing.spreadsheet_id');
        $url = rtrim((string) config('google_sheets.billing.api_base_url'), '/').'/'.rawurlencode($spreadsheetId).'/values/'.rawurlencode($range).'?valueInputOption=RAW';
        $response = $this->request()->put($url, [
            'range' => $range,
            'majorDimension' => 'ROWS',
            'values' => [[$value]],
        ]);
        $this->throwForFailedResponse($response->status(), $response->json('error.message'));
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
        $credentialsPath = (string) config('google_sheets.billing.service_account_json');
        $credentials = json_decode(File::get($credentialsPath), true, 512, JSON_THROW_ON_ERROR);
        $cacheKey = 'google_sheets_access_token_'.hash('sha256', (string) $credentials['client_email']);
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
            $credentialsPath = (string) config('google_sheets.billing.service_account_json');
            $credentials = is_file($credentialsPath) ? json_decode(file_get_contents($credentialsPath), true) : [];
            Cache::forget('google_sheets_access_token_'.hash('sha256', (string) ($credentials['client_email'] ?? '')));
        }

        $detail = is_string($apiMessage) && $apiMessage !== '' ? ' '.mb_substr($apiMessage, 0, 500) : '';
        throw new GoogleSheetsApiException('Google Sheets API request failed (HTTP '.$statusCode.').'.$detail, $statusCode);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
