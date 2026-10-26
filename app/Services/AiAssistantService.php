<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AiAssistantService
{
    public function __construct(private AiProviderConfiguration $configuration, private OpenAiResponsesClient $client) {}

    /** @param array<int, array{role: string, content: string}> $history
     * @param  callable(string): void  $onDelta
     * @return array{model: string, request_id: ?string, input_tokens: ?int, output_tokens: ?int}
     */
    public function respond(User $user, Company $company, array $context, array $history, callable $onDelta, ?callable $onProductMatches = null): array
    {
        if (! $this->configuration->isEnabled()) {
            throw new RuntimeException('The AI assistant is currently disabled.');
        }

        $key = $this->configuration->apiKey();
        $model = $this->configuration->model();
        if ($key === null || $model === '') {
            throw new RuntimeException('The AI assistant is not configured.');
        }
        if ($this->configuration->monthlyLimitReached()) {
            throw new RuntimeException('The system monthly AI token limit has been reached.');
        }

        $gateway = app(AiReadOnlyToolGateway::class);
        $tools = $gateway->tools($user, $company, $context);
        $input = array_map(fn (array $message): array => ['role' => $message['role'], 'content' => $message['content']], $history);
        $requiredTool = $this->requiredReadTool($history, $tools);
        $usage = ['model' => $model, 'request_id' => null, 'input_tokens' => null, 'output_tokens' => null];
        $emitted = '';

        for ($round = 0; $round < 3; $round++) {
            $requestBody = [
                'model' => $model,
                'instructions' => $this->instructions(
                    $company,
                    $context,
                    $user->can('mi.product-prices.view'),
                    $user->can('mi.quotation.create') && Schema::hasTable('ai_quotation_workflows') && Schema::hasTable('ai_quotations'),
                ),
                'input' => $input,
                'tools' => $tools,
                'stream' => true,
                'store' => false,
                'max_output_tokens' => (int) config('ai_assistant.max_output_tokens', 1200),
            ];
            if ($round === 0 && $requiredTool !== null) {
                $requestBody['tool_choice'] = ['type' => 'function', 'name' => $requiredTool];
                Log::info('AI assistant required tool selected by intent', [
                    'tool' => $requiredTool,
                    'user_id' => $user->getKey(),
                    'company_id' => $company->getKey(),
                ]);
            }
            $request = $this->client->stream($key, $requestBody);

            if (! $request->successful()) {
                throw new RuntimeException('The AI provider request failed.');
            }

            $response = $request->toPsrResponse();
            $usage['request_id'] = $response->getHeaderLine('x-request-id') ?: $usage['request_id'];
            $body = $response->getBody();
            $buffer = '';
            $toolCalls = [];
            $responseOutput = [];
            $completed = false;
            while (! $body->eof()) {
                $buffer .= $body->read(2048);
                while (($lineEnd = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $lineEnd));
                    $buffer = substr($buffer, $lineEnd + 1);
                    if (! str_starts_with($line, 'data:')) {
                        continue;
                    }

                    $event = json_decode(trim(substr($line, 5)), true);
                    if (! is_array($event)) {
                        continue;
                    }

                    $type = $event['type'] ?? '';
                    if ($type === 'response.output_text.delta' && isset($event['delta'])) {
                        $delta = (string) $event['delta'];
                        $emitted .= $delta;
                        $onDelta($delta);
                    } elseif ($type === 'response.output_item.done' && ($event['item']['type'] ?? null) === 'function_call') {
                        $toolCalls[] = $event['item'];
                    } elseif ($type === 'response.completed') {
                        $completed = true;
                        $responseOutput = $event['response']['output'] ?? [];
                        $usage['input_tokens'] = $event['response']['usage']['input_tokens'] ?? null;
                        $usage['output_tokens'] = $event['response']['usage']['output_tokens'] ?? null;
                    } elseif ($type === 'error') {
                        throw new RuntimeException('The AI provider reported an error.');
                    }
                }
            }

            if (! $completed) {
                throw new RuntimeException('The AI provider response ended unexpectedly.');
            }

            if ($toolCalls === []) {
                if ($round > 0 && $emitted === '') {
                    foreach ($responseOutput as $item) {
                        foreach ($item['content'] ?? [] as $content) {
                            if (($content['type'] ?? null) === 'output_text' && isset($content['text'])) {
                                $onDelta((string) $content['text']);
                                $emitted .= (string) $content['text'];
                            }
                        }
                    }
                }

                return $usage;
            }

            $input = [...$input, ...$responseOutput];
            foreach ($toolCalls as $call) {
                $arguments = json_decode((string) ($call['arguments'] ?? '{}'), true);
                $result = $gateway->call((string) $call['name'], is_array($arguments) ? $arguments : [], $user, $company);
                if ($call['name'] === 'lookup_mi_product_prices' && $onProductMatches !== null && $result !== []) {
                    $onProductMatches($result);
                }
                $input[] = ['type' => 'function_call_output', 'call_id' => $call['call_id'], 'output' => json_encode($result, JSON_THROW_ON_ERROR)];
            }
        }

        throw new RuntimeException('The assistant reached its tool-call limit.');
    }

    /** @param array<int, array{role: string, content: string}> $history
     * @param  array<int, array<string, mixed>>  $tools
     */
    private function requiredReadTool(array $history, array $tools): ?string
    {
        $lastUserMessage = collect($history)->last(fn (array $message): bool => $message['role'] === 'user')['content'] ?? '';
        if (! is_string($lastUserMessage) || preg_match('/\b(budget|expense|forecast|project|delivery|inventory|liquidation)\b/i', $lastUserMessage)) {
            return null;
        }

        $asksForPrice = preg_match('/\b(how\s+much\s+is|price\s+(?:of|for)|selling\s+price\s+(?:of|for)|what\s+is\s+the\s+price\s+of|cost\s+of)\b/i', $lastUserMessage) === 1;
        $priceToolAvailable = collect($tools)->contains(fn (array $tool): bool => ($tool['name'] ?? null) === 'lookup_mi_product_prices');

        return $asksForPrice && $priceToolAvailable ? 'lookup_mi_product_prices' : null;
    }

    /** @param array<string, mixed> $context */
    private function instructions(Company $company, array $context, bool $canViewProductPrices, bool $canCreateQuotation): string
    {
        $priceGuidance = $canViewProductPrices
            ? 'The user is authorized to use the MI selling-price lookup when it is supplied.'
            : 'The user is not authorized to view MI selling prices; do not infer that a price is missing, and explain that price-view access is required.';
        $quotationGuidance = $canCreateQuotation
            ? 'The user is authorized to prepare a quotation; offer the existing quotation workflow as an optional next step. The user must explicitly confirm before a draft is saved.'
            : 'The user cannot currently create quotations; do not offer or imply access to quotation creation.';

        return 'You are the Project Tracker assistant for the active company '.$company->code.'. Use the page context to explain the current page, but use any supplied tool the user is authorized to access even when it belongs to another module in this active company. Never claim access to another company. Use only supplied read-only tools for saved business records; never invent SQL or request arbitrary database access. Do not expose secrets or ask for passwords. Page context contains route metadata only, never form values. Never create, edit, delete, submit, release, or approve records. For MI product price questions, call lookup_mi_product_prices when available; this search is allowed independently of quotation creation permission. Use search_mi_products for catalog details without prices. '.$priceGuidance.' Report only a price and SKU returned by the price tool, state PHP currency and MI product selling price basis, and never use factory or purchase costs as selling prices. If multiple similar products are returned, present the distinct options and ask which one the user means; do not assume Round Table and Round Side Table are identical. '.$quotationGuidance.' Do not invent taxes, discounts, delivery fees, payment terms, forecasts, or data that no tool returns. For COP tools, explain if tracking is unavailable rather than guessing.\n\nAuthorized page context: '.json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
