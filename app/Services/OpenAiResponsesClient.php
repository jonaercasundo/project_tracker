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

    public function searchProductOffers(string $apiKey, string $model, string $productName, ?string $refinement, ?string $referenceUrl, int $maxResults, ?string $description = null): Response
    {
        $payload = [
            'model' => $model,
            'tools' => [['type' => 'web_search', 'search_context_size' => 'medium', 'external_web_access' => true]],
            'include' => ['web_search_call.action.sources'],
            'tool_choice' => 'required',
            'max_output_tokens' => max(2500, $maxResults * 400),
            'store' => false,
            'input' => [
                [
                    'role' => 'system',
                    'content' => ProductResearchPrompt::system(),
                ],
                [
                    'role' => 'user',
                    'content' => ProductResearchPrompt::user($productName, $description, $refinement, $referenceUrl, $maxResults),
                ],
            ],
        ];

        try {
            return $this->request($apiKey)->acceptJson()->post('https://api.openai.com/v1/responses', $payload);
        } catch (ConnectionException) {
            throw new RuntimeException('The OpenAI search connection failed.');
        }
    }

    /** @return array{status: string, message: string} */
    public function testConnection(string $apiKey, string $model, bool $webSearch = false): array
    {
        $payload = [
            'model' => $model,
            'input' => $webSearch ? 'Use web search to find the official OpenAI API documentation. Reply with its URL only.' : 'Reply with OK.',
            'max_output_tokens' => $webSearch ? 1000 : 10,
            'store' => false,
        ];
        if ($webSearch) {
            $payload['tools'] = [['type' => 'web_search']];
            $payload['tool_choice'] = 'required';
        }
        try {
            $response = $this->request($apiKey)->acceptJson()->post('https://api.openai.com/v1/responses', $payload);
        } catch (ConnectionException) {
            return ['status' => 'network_error', 'message' => 'Could not reach OpenAI. Check outbound network access and try again.'];
        }

        if ($response->successful()) {
            if ($webSearch) {
                $searchCompleted = collect($response->json('output', []))->contains(fn (mixed $item): bool => is_array($item) && ($item['type'] ?? null) === 'web_search_call' && ($item['status'] ?? null) === 'completed');
                if (! $searchCompleted || in_array($response->json('status'), ['incomplete', 'failed'], true)) {
                    return ['status' => 'web_search_unverified', 'message' => 'The API accepted the request but did not complete web search. Check the model and web-search access, then retry.'];
                }

                return ['status' => 'success', 'message' => 'OpenAI completed a web search with this model and credential. Product research can use this capability.'];
            }

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
            $quotaCodes = ['credit_balance_exhausted', 'insufficient_quota', 'billing_hard_limit_reached', 'billing_not_active'];
            if (in_array($code, $quotaCodes, true)) {
                return ['status' => 'quota_or_billing', 'message' => 'OpenAI API credits are exhausted or billing is inactive. Add credits to the API project or use a funded project key.'];
            }

            return ['status' => 'rate_limited', 'message' => 'OpenAI rate-limited the connection test. Wait briefly and try again.'];
        }

        if (in_array($code, ['model_not_found', 'unsupported_model'], true)) {
            return ['status' => 'model_unavailable', 'message' => 'The credential was accepted, but the selected model is unavailable to this project.'];
        }
        if ($webSearch && $response->status() === 400) {
            return ['status' => 'web_search_unavailable', 'message' => 'OpenAI rejected the web-search request. Select a model with Responses API web-search support and verify project access.'];
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
