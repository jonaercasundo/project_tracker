<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class MmcPlatformApiClient
{
    public function __construct(private MmcPlatformConfiguration $configuration) {}

    /** @return array{configured: bool, secure: bool, base_url: ?string, has_key: bool, credential_source: string, storage_available: bool, reason: ?string} */
    public function configurationStatus(): array
    {
        $configuration = $this->configuration->current();
        $baseUrl = trim($configuration['base_url']);
        $readKey = trim($configuration['read_key']);

        if ($baseUrl === '') {
            return [
                'configured' => false,
                'secure' => false,
                'base_url' => null,
                'has_key' => $readKey !== '',
                'credential_source' => $configuration['credential_source'],
                'storage_available' => $configuration['storage_available'],
                'reason' => $configuration['credential_error'] ?? 'Enter an API base URL and read-only API key.',
            ];
        }

        $urlError = $this->baseUrlError($baseUrl);
        if ($urlError !== null) {
            return [
                'configured' => false,
                'secure' => false,
                'base_url' => null,
                'has_key' => $readKey !== '',
                'credential_source' => $configuration['credential_source'],
                'storage_available' => $configuration['storage_available'],
                'reason' => $configuration['credential_error'] ?? $urlError,
            ];
        }

        if ($readKey === '' || $configuration['credential_error'] !== null) {
            return [
                'configured' => false,
                'secure' => str_starts_with(strtolower($baseUrl), 'https://'),
                'base_url' => rtrim($baseUrl, '/'),
                'has_key' => false,
                'credential_source' => $configuration['credential_source'],
                'storage_available' => $configuration['storage_available'],
                'reason' => $configuration['credential_error'] ?? 'Enter a read-only API key beginning with mmc_.',
            ];
        }

        return [
            'configured' => true,
            'secure' => str_starts_with(strtolower($baseUrl), 'https://'),
            'base_url' => rtrim($baseUrl, '/'),
            'has_key' => true,
            'credential_source' => $configuration['credential_source'],
            'storage_available' => $configuration['storage_available'],
            'reason' => null,
        ];
    }

    public function baseUrlError(string $baseUrl): ?string
    {
        $parts = parse_url(trim($baseUrl));
        if (! is_array($parts)
            || ! filter_var(trim($baseUrl), FILTER_VALIDATE_URL)
            || ! in_array(strtolower($parts['scheme'] ?? ''), ['https', 'http'], true)
            || ! isset($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])) {
            return 'The MMC API base URL is invalid. Use a URL without credentials, query parameters, or a fragment.';
        }

        $host = trim(strtolower($parts['host']), '[]');
        $isLoopback = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
        if (strtolower($parts['scheme']) !== 'https' && ! $isLoopback) {
            return 'Use HTTPS before sending an API key to a non-local host.';
        }

        return null;
    }

    /** @return array{status: string, message: string, preview: array<string, scalar|null>} */
    public function testConnection(?string $baseUrl = null, ?string $readKey = null): array
    {
        $configuration = $this->configuration->current();
        $baseUrl = trim($baseUrl ?? $configuration['base_url']);
        $readKey = trim($readKey ?? '') !== '' ? trim((string) $readKey) : trim($configuration['read_key']);
        $urlError = $this->baseUrlError($baseUrl);
        if ($urlError !== null) {
            return ['status' => 'failed', 'message' => $urlError, 'preview' => []];
        }
        if (! str_starts_with($readKey, 'mmc_')) {
            return ['status' => 'failed', 'message' => 'Enter a valid read-only API key beginning with mmc_.', 'preview' => []];
        }

        $endpoint = rtrim($baseUrl, '/').'/programs';
        try {
            $response = $this->authenticatedRequest($readKey)->get($endpoint, ['limit' => 1]);
        } catch (ConnectionException) {
            return ['status' => 'failed', 'message' => 'Could not reach the MMC Platform. Check the URL, TLS certificate, network route, and service availability.', 'preview' => []];
        } catch (Throwable) {
            return ['status' => 'failed', 'message' => 'The MMC Platform connection test failed without exposing the API key.', 'preview' => []];
        }

        if (! $response->successful()) {
            $message = match ($response->status()) {
                401, 403 => 'The API key was rejected or does not have the read scope.',
                404 => 'The configured host does not expose GET /api/v1/programs at this base URL. Check the API base URL.',
                429 => 'The platform rate limit was reached. Wait before testing again.',
                default => 'The MMC Platform returned HTTP '.$response->status().'.',
            };

            return ['status' => 'failed', 'message' => $message, 'preview' => []];
        }

        $preview = $this->safeProgramPreview($response->json());

        return [
            'status' => 'success',
            'message' => 'Tracker authenticated and reached the MMC Platform read API.',
            'preview' => $preview,
        ];
    }

    /** @return array{status: string, message: string, programs: array, raw: mixed} */
    public function getPrograms(): array
    {
        $result = $this->getReadResponse('/programs', []);
        if ($result['error'] !== null) {
            return ['status' => 'failed', 'message' => $result['error'], 'programs' => [], 'raw' => $result['raw']];
        }

        $payload = $result['payload'];
        $programs = is_array($payload) ? ($payload['data'] ?? $payload['programs'] ?? $payload) : null;
        if (! is_array($programs) || (! array_is_list($programs) && ! isset($programs['id']))) {
            return ['status' => 'failed', 'message' => 'The programs endpoint returned an unexpected response. Confirm its response format with the MMC API owner.', 'programs' => [], 'raw' => $this->safeRaw($result['raw'])];
        }

        return ['status' => 'success', 'message' => 'Connection successful.', 'programs' => array_values(array_filter($programs, fn ($program): bool => is_array($program) && isset($program['id']))), 'raw' => $this->safeRaw($result['raw'])];
    }

    /** @return array{status: string, message: string, records: array, raw: mixed, retrieved_at: string, diagnostics: array} */
    public function previewMasterList(string $programId, string $programName, int $limit): array
    {
        $path = '/programs/'.rawurlencode($programId).'/master-list';
        $timestamp = now()->toIso8601String();
        $records = [];
        $requests = [];
        $total = null;
        $hasMore = null;
        $dataCount = 0;
        $raw = null;
        $message = 'Data retrieved successfully.';
        $status = 'success';
        $maxPages = 10;

        for ($page = 1; $page <= $maxPages && count($records) < $limit; $page++) {
            $parameters = ['page' => $page, 'limit' => min(max(1, $limit - count($records)), 2000)];
            $result = $this->getReadResponse($path, $parameters);
            $requests[] = [
                'http_status' => $result['http_status'],
                'requested_endpoint' => $result['requested_endpoint'],
                'parameters' => $parameters,
            ];
            $raw = $result['raw'];

            if ($result['error'] !== null) {
                $status = $records === [] ? 'failed' : 'partial';
                $message = $records === [] ? $result['error'] : 'Some records were retrieved, but a later page failed: '.$result['error'];
                break;
            }

            $payload = $result['payload'];
            $pageRecords = is_array($payload) ? ($payload['data'] ?? $payload['master_list'] ?? $payload['records'] ?? $payload) : null;
            if (! is_array($pageRecords) || ! array_is_list($pageRecords) || array_filter($pageRecords, fn ($record): bool => ! is_array($record)) !== []) {
                $status = $records === [] ? 'failed' : 'partial';
                $message = 'The master-list endpoint returned an unexpected response shape. Inspect the safe raw response and confirm the documented response envelope with the MMC API owner.';
                break;
            }

            $records = array_merge($records, $pageRecords);
            $dataCount += count($pageRecords);
            $total = $this->responseValue($payload, 'total');
            $hasMore = $this->responseValue($payload, 'has_more');
            if (filter_var($hasMore, FILTER_VALIDATE_BOOLEAN) !== true || count($records) >= $limit) {
                break;
            }

            if ($page === $maxPages) {
                $status = 'partial';
                $message = 'The preview reached its 10-page safety limit while the API still reports more data. Increase the sample selectively or ask the API owner about the response pagination.';
            }
        }

        $records = array_slice($records, 0, $limit);
        $diagnostics = [
            'http_status' => $requests === [] ? null : $requests[array_key_last($requests)]['http_status'],
            'requested_endpoint' => $requests[0]['requested_endpoint'] ?? null,
            'requests' => $requests,
            'program_name' => $programName,
            'program_id' => $programId,
            'parameters' => array_column($requests, 'parameters'),
            'total' => $total,
            'data_count' => $dataCount,
            'has_more' => $hasMore,
        ];
        $totalIsZero = is_numeric($diagnostics['total']) && (float) $diagnostics['total'] === 0.0;
        if ($status === 'success' && $records === []) {
            $message = $totalIsZero && filter_var($diagnostics['has_more'], FILTER_VALIDATE_BOOLEAN) === true
                ? 'The MMC Platform returned total: 0 and an empty data array, but has_more is true. Ask the API owner to verify the program ID and pagination metadata.'
                : ($totalIsZero
                    ? 'The MMC Platform returned total: 0 and an empty data array for this program. Ask the API owner to verify this exact program ID is populated and visible to the read-scoped key.'
                    : 'The MMC Platform returned no records in the requested pages. Review total and has_more below, then ask the API owner to verify the program’s master-list data.');
        }

        return ['status' => $status, 'message' => $message, 'records' => $records, 'raw' => $raw, 'retrieved_at' => $timestamp, 'diagnostics' => $diagnostics];
    }

    /** @return array{error: ?string, payload: mixed, raw: mixed, http_status: ?int, requested_endpoint: ?string} */
    private function getReadResponse(string $path, array $query): array
    {
        $configuration = $this->configuration->current();
        if ($this->baseUrlError($configuration['base_url']) !== null || ! str_starts_with($configuration['read_key'], 'mmc_')) {
            return ['error' => 'Configure a valid API base URL and read-scoped mmc_ bearer key first.', 'payload' => null, 'raw' => null, 'http_status' => null, 'requested_endpoint' => null];
        }

        $requestedEndpoint = rtrim($configuration['base_url'], '/').$path.'?'.http_build_query($query);
        try {
            $response = $this->authenticatedRequest($configuration['read_key'])->get(rtrim($configuration['base_url'], '/').$path, $query);
        } catch (ConnectionException) {
            return ['error' => 'The MMC Platform is unavailable. Check network access, TLS, and whether the API service is running.', 'payload' => null, 'raw' => null, 'http_status' => null, 'requested_endpoint' => $requestedEndpoint];
        } catch (Throwable) {
            return ['error' => 'The MMC Platform request failed. Check the API host and try again.', 'payload' => null, 'raw' => null, 'http_status' => null, 'requested_endpoint' => $requestedEndpoint];
        }

        $raw = $response->body();
        if (! $response->successful()) {
            $message = match ($response->status()) {
                401, 403 => 'Authentication failed. Confirm the bearer key is valid and has read scope.',
                404 => str_contains($path, '/master-list') ? 'The program ID was not found, or the master-list endpoint is unavailable. Verify the selected program and confirm the endpoint with the MMC API owner.' : 'GET /api/v1/programs is unavailable at this base URL. Check the API base URL.',
                429 => 'The platform rate limit was reached. Wait before trying again.',
                default => 'The MMC Platform returned HTTP '.$response->status().'. Check service availability and try again.',
            };

            return ['error' => $message, 'payload' => null, 'raw' => $this->safeRaw($raw), 'http_status' => $response->status(), 'requested_endpoint' => $requestedEndpoint];
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return ['error' => 'The MMC Platform returned malformed JSON. Ask the API owner to verify the response.', 'payload' => null, 'raw' => $this->safeRaw($raw), 'http_status' => $response->status(), 'requested_endpoint' => $requestedEndpoint];
        }

        return ['error' => null, 'payload' => $payload, 'raw' => $this->safeRaw($payload), 'http_status' => $response->status(), 'requested_endpoint' => $requestedEndpoint];
    }

    private function responseValue(array $payload, string $key): mixed
    {
        foreach ([$payload, $payload['meta'] ?? null, $payload['pagination'] ?? null] as $container) {
            if (is_array($container) && array_key_exists($key, $container)) {
                $value = $container[$key];

                return is_scalar($value) || $value === null ? $value : null;
            }
        }

        return null;
    }

    private function safeRaw(mixed $payload): mixed
    {
        $secret = trim($this->configuration->current()['read_key']);
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return mb_substr($secret === '' ? $payload : str_replace($secret, '[REDACTED]', $payload), 0, 12000);
            }
            $payload = $decoded;
        }
        if (is_array($payload)) {
            $safe = [];
            foreach ($payload as $key => $value) {
                if (preg_match('/authorization|bearer|token|secret|api.?key/i', (string) $key)) {
                    $safe[$key] = '[REDACTED]';
                } else {
                    $safe[$key] = is_array($value) ? $this->safeRaw($value) : (is_string($value) ? mb_substr($secret === '' ? $value : str_replace($secret, '[REDACTED]', $value), 0, 2000) : $value);
                }
            }

            return $safe;
        }

        return is_string($payload) && $secret !== '' ? str_replace($secret, '[REDACTED]', $payload) : $payload;
    }

    /** @param array<string, scalar|null> $query */
    public function getFeedPage(string $feed, array $query = []): array
    {
        if (! in_array($feed, ['sync/lots', 'sync/schools', 'sync/deliveries'], true)) {
            throw new \InvalidArgumentException('Unsupported MMC Platform feed.');
        }

        $configuration = $this->configuration->current();
        if ($this->baseUrlError($configuration['base_url']) !== null || ! str_starts_with($configuration['read_key'], 'mmc_')) {
            throw new \RuntimeException('MMC Platform API configuration is invalid.');
        }

        $verification = $this->testConnection($configuration['base_url'], $configuration['read_key']);
        if ($verification['status'] !== 'success') {
            throw new \RuntimeException('MMC Platform host verification failed: '.$verification['message']);
        }

        $response = $this->authenticatedRequest($configuration['read_key'])->get(rtrim($configuration['base_url'], '/').'/'.$feed, $query);
        $response->throw();
        $payload = $response->json();
        if (! is_array($payload)) {
            throw new \UnexpectedValueException('The MMC Platform returned an invalid feed response.');
        }

        return $payload;
    }

    private function unauthenticatedRequest(): PendingRequest
    {
        return Http::withoutRedirecting()
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(8);
    }

    private function authenticatedRequest(string $readKey): PendingRequest
    {
        return Http::withoutRedirecting()
            ->withToken($readKey)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->retry([500, 1000, 2000], 0, function (Throwable $exception): bool {
                return $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && ($exception->response->serverError() || $exception->response->status() === 429));
            }, false);
    }

    /**
     * @return array<string, scalar|null>
     */
    private function safeProgramPreview(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $programs = $payload['data'] ?? $payload['programs'] ?? $payload;
        if (! is_array($programs)) {
            return [];
        }

        if (array_is_list($programs)) {
            $program = $programs[0] ?? null;
        } else {
            $program = $programs;
        }
        if (! is_array($program)) {
            return [];
        }

        $preview = [];
        foreach (['id', 'name', 'project_title', 'fiscal_year'] as $field) {
            $value = $program[$field] ?? null;
            if (is_scalar($value) || $value === null) {
                $preview[$field] = is_string($value) ? mb_substr($value, 0, 160) : $value;
            }
        }

        return $preview;
    }
}
