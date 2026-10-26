<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiResponsesClient
{
    public function stream(string $apiKey, array $payload): Response
    {
        try {
            return $this->request($apiKey, true)->accept('text/event-stream')->withOptions(['stream' => true])
                ->post('https://api.openai.com/v1/responses', $payload);
        } catch (ConnectionException) {
            throw new RuntimeException('The OpenAI connection failed.');
        }
    }

    /** @return array{status: string, message: string} */
    public function testConnection(string $apiKey, string $model): array
    {
        try {
            $response = $this->request($apiKey)->acceptJson()->post('https://api.openai.com/v1/responses', [
                'model' => $model,
                'input' => 'Reply with OK.',
                'max_output_tokens' => 10,
                'store' => false,
            ]);
        } catch (ConnectionException) {
            return ['status' => 'network_error', 'message' => 'Could not reach OpenAI. Check outbound network access and try again.'];
        }

        if ($response->successful()) {
            return ['status' => 'success', 'message' => 'OpenAI accepted a minimal Responses API request for this model. This does not verify every model feature.'];
        }

        $providerCode = $response->json('error.code');
        $providerType = $response->json('error.type');
        $code = is_string($providerCode) ? $providerCode : (is_string($providerType) ? $providerType : null);

        if ($response->status() === 401) {
            return ['status' => 'invalid_credentials', 'message' => 'OpenAI rejected this API key. Check the key and replace it if needed.'];
        }

        if ($response->status() === 403) {
            return ['status' => 'insufficient_permissions', 'message' => 'The key is recognized, but this project or organization cannot use the requested API capability.'];
        }

        if ($response->status() === 429) {
            $quotaCodes = ['insufficient_quota', 'billing_hard_limit_reached', 'billing_not_active'];
            if (in_array($code, $quotaCodes, true)) {
                return ['status' => 'quota_or_billing', 'message' => 'The OpenAI project has insufficient quota or a billing limit. Review its API billing and usage limits.'];
            }

            return ['status' => 'rate_limited', 'message' => 'OpenAI rate-limited the connection test. Wait briefly and try again.'];
        }

        if (in_array($code, ['model_not_found', 'unsupported_model'], true)) {
            return ['status' => 'model_unavailable', 'message' => 'The credential was accepted, but the selected model is unavailable to this project.'];
        }

        if ($response->serverError()) {
            return ['status' => 'provider_outage', 'message' => 'OpenAI returned a temporary server error. Try again later.'];
        }

        return ['status' => 'request_rejected', 'message' => 'OpenAI rejected the test request. Check model access and request limits.'];
    }

    private function request(string $apiKey, bool $stream = false): PendingRequest
    {
        $request = Http::withToken($apiKey)->timeout((int) config('services.openai.assistant_timeout', 90));

        return $stream ? $request : $request->connectTimeout(10);
    }
}
