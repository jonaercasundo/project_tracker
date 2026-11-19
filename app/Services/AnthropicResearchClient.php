<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnthropicResearchClient
{
    public function searchProductOffers(string $apiKey, string $model, string $productName, ?string $refinement, ?string $referenceUrl, int $maxResults, ?string $description = null): Response
    {
        $payload = [
            'model' => $model,
            'max_tokens' => max(6000, $maxResults * 400),
            'system' => ProductResearchPrompt::system(),
            'messages' => [['role' => 'user', 'content' => ProductResearchPrompt::user($productName, $description, $refinement, $referenceUrl, $maxResults)]],
            'tools' => [['type' => 'web_search_20250305', 'name' => 'web_search', 'max_uses' => 10]],
        ];
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $response = $this->request($apiKey, $payload);
            if (! $response->successful() || $response->json('stop_reason') !== 'pause_turn') {
                return $response;
            }
            $payload['messages'][] = ['role' => 'assistant', 'content' => $response->json('content', [])];
        }

        return $response;
    }

    /** @return array<int, array<string, mixed>> */
    public function output(Response $response): array
    {
        if ($response->json('stop_reason') !== 'end_turn') {
            throw new RuntimeException('Claude research did not complete.');
        }
        $text = '';
        $sources = [];
        foreach ($response->json('content', []) as $block) {
            if (($block['type'] ?? null) === 'text') {
                $text .= $block['text'] ?? '';
                foreach ($block['citations'] ?? [] as $citation) {
                    if (is_string($citation['url'] ?? null)) {
                        $sources[] = array_diff_key($citation, ['encrypted_index' => true]);
                    }
                }
            }
            if (($block['type'] ?? null) === 'web_search_tool_result') {
                if (($block['content']['type'] ?? null) === 'web_search_tool_result_error') {
                    throw new RuntimeException('Claude web search returned a tool error.');
                }
                foreach ($block['content'] ?? [] as $source) {
                    if (is_array($source) && is_string($source['url'] ?? null)) {
                        $sources[] = array_diff_key($source, ['encrypted_content' => true]);
                    }
                }
            }
        }

        return [
            ['type' => 'web_search_call', 'action' => ['sources' => $sources]],
            ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => $text]]],
        ];
    }

    /** @return array{status: string, message: string, setup_required: bool} */
    public function failure(Response $response): array
    {
        $billing = $response->status() === 402 || ($response->status() === 400 && preg_match('/credit balance|billing|purchase credits/i', (string) $response->json('error.message')));
        if ($billing) {
            return ['status' => 'quota_or_billing', 'message' => 'Claude API credits are exhausted or billing is inactive. Fund the Anthropic API account or replace the Claude key in AI Provider Settings.', 'setup_required' => true];
        }

        return match ($response->status()) {
            401 => ['status' => 'invalid_credentials', 'message' => 'Claude rejected the API key. Update the Claude key in AI Provider Settings.', 'setup_required' => true],
            403 => ['status' => 'insufficient_permissions', 'message' => 'Claude denied model or web-search access. Check permissions and enable web search in the Claude Console.', 'setup_required' => true],
            400, 404 => ['status' => 'model_unavailable', 'message' => 'Claude rejected the model or web-search configuration. Check the model and web-search access in the Claude Console.', 'setup_required' => true],
            default => ['status' => 'provider_unavailable', 'message' => 'Claude is temporarily unavailable or rate-limited. Try again later.', 'setup_required' => false],
        };
    }

    /** @return array{status: string, message: string} */
    public function testConnection(string $apiKey, string $model, bool $webSearch = false): array
    {
        $payload = ['model' => $model, 'max_tokens' => $webSearch ? 1000 : 64, 'messages' => [['role' => 'user', 'content' => $webSearch ? 'Use web search to find the official Anthropic API documentation. Reply with its URL.' : 'Reply with OK.']]];
        if ($webSearch) {
            $payload['tools'] = [['type' => 'web_search_20250305', 'name' => 'web_search', 'max_uses' => 1]];
        }
        try {
            $response = $this->request($apiKey, $payload);
        } catch (ConnectionException) {
            return ['status' => 'network_error', 'message' => 'Could not reach Claude. Check outbound network access and try again.'];
        }
        if (! $response->successful()) {
            $failure = $this->failure($response);

            return ['status' => $failure['status'], 'message' => $failure['message']];
        }
        if ($webSearch) {
            try {
                $output = $this->output($response);
                if ($output[0]['action']['sources'] === []) {
                    throw new RuntimeException('No web sources returned.');
                }
            } catch (RuntimeException) {
                return ['status' => 'web_search_unverified', 'message' => 'Claude did not complete a usable web search. Enable web search in the Claude Console and check model access, then retry.'];
            }
        }

        return ['status' => 'success', 'message' => $webSearch ? 'Claude completed a web search using this credential and model.' : 'Claude accepted an API request using this credential and model.'];
    }

    /** @param array<string, mixed> $payload */
    private function request(string $apiKey, array $payload): Response
    {
        return Http::withHeaders(['x-api-key' => $apiKey, 'anthropic-version' => '2023-06-01'])
            ->acceptJson()->connectTimeout(10)->timeout((int) config('services.openai.assistant_timeout', 90))
            ->post('https://api.anthropic.com/v1/messages', $payload);
    }
}
