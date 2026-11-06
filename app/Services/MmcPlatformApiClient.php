<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class MmcPlatformApiClient
{
    /** @return array{configured: bool, secure: bool, base_url: ?string, reason: ?string} */
    public function configurationStatus(): array
    {
        $baseUrl = trim((string) config('services.mmc_platform.base_url'));
        $readKey = trim((string) config('services.mmc_platform.read_key'));

        if ($baseUrl === '') {
            return [
                'configured' => false,
                'secure' => false,
                'base_url' => null,
                'reason' => 'Set MMC_PLATFORM_API_BASE_URL and MMC_PLATFORM_READ_KEY in the server environment.',
            ];
        }

        $parts = parse_url($baseUrl);
        if (! is_array($parts)
            || ! in_array($parts['scheme'] ?? '', ['https', 'http'], true)
            || ! isset($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])) {
            return ['configured' => false, 'secure' => false, 'base_url' => null, 'reason' => 'The MMC API base URL is invalid.'];
        }

        $isLoopback = in_array(strtolower($parts['host']), ['localhost', '127.0.0.1', '::1'], true);
        $secure = ($parts['scheme'] ?? '') === 'https';
        $safeBaseUrl = rtrim($baseUrl, '/');
        if (! $secure && ! $isLoopback) {
            return ['configured' => false, 'secure' => false, 'base_url' => $safeBaseUrl, 'reason' => 'Use HTTPS before sending an API key over the network. HTTP is allowed only for a loopback development endpoint.'];
        }

        if ($readKey === '') {
            return [
                'configured' => false,
                'secure' => $secure,
                'base_url' => $safeBaseUrl,
                'reason' => 'Set MMC_PLATFORM_API_BASE_URL and MMC_PLATFORM_READ_KEY in the server environment.',
            ];
        }

        return ['configured' => true, 'secure' => $secure, 'base_url' => $safeBaseUrl, 'reason' => null];
    }

    /** @return array{status: string, message: string} */
    public function testConnection(): array
    {
        $status = $this->configurationStatus();
        if (! $status['configured']) {
            return ['status' => 'configuration_required', 'message' => $status['reason'] ?? 'MMC Platform is not configured.'];
        }

        try {
            $response = $this->request()->get('programs', ['limit' => 1]);
        } catch (ConnectionException) {
            return ['status' => 'failed', 'message' => 'Could not reach the MMC Platform API. Check the host, network route, and service availability.'];
        } catch (Throwable) {
            return ['status' => 'failed', 'message' => 'The MMC Platform connection test failed.'];
        }

        if ($response->successful()) {
            return ['status' => 'success', 'message' => 'Tracker authenticated and reached the MMC Platform read API.'];
        }

        return match ($response->status()) {
            401, 403 => ['status' => 'failed', 'message' => 'The API key was rejected or does not have the read scope.'],
            404 => ['status' => 'failed', 'message' => 'The configured API base URL does not expose the expected /programs endpoint.'],
            429 => ['status' => 'failed', 'message' => 'The platform rate limit was reached. Wait before testing again.'],
            default => ['status' => 'failed', 'message' => 'The MMC Platform returned HTTP '.$response->status().'.'],
        };
    }

    /** @param array<string, scalar|null> $query */
    public function getFeedPage(string $feed, array $query = []): array
    {
        if (! in_array($feed, ['sync/lots', 'sync/schools', 'sync/deliveries'], true)) {
            throw new \InvalidArgumentException('Unsupported MMC Platform feed.');
        }

        $response = $this->request()->get($feed, $query);
        $response->throw();
        $payload = $response->json();
        if (! is_array($payload)) {
            throw new \UnexpectedValueException('The MMC Platform returned an invalid feed response.');
        }

        return $payload;
    }

    private function request(): PendingRequest
    {
        $status = $this->configurationStatus();
        if (! $status['configured']) {
            throw new \RuntimeException($status['reason'] ?? 'MMC Platform is not configured.');
        }

        return Http::baseUrl($status['base_url'])
            ->withToken((string) config('services.mmc_platform.read_key'))
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->retry([500, 1000, 2000], 0, function (Throwable $exception): bool {
                return $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && ($exception->response->serverError() || $exception->response->status() === 429));
            }, false);
    }
}
