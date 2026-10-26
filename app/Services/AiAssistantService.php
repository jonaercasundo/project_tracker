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
        $requiredTool = $this->requiredReadTool($history, $tools, $context);
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
    private function requiredReadTool(array $history, array $tools, array $context): ?string
    {
        $lastUserMessage = collect($history)->last(fn (array $message): bool => $message['role'] === 'user')['content'] ?? '';
        if (! is_string($lastUserMessage)) {
            return null;
        }

        $availableTools = collect($tools)->pluck('name')->all();
        if (preg_match('/\b(?:missing\s+(?:cop|country\s+of\s+production)|cop\s+(?:updates|missing)|products?\s+needing\s+cop)\b/i', $lastUserMessage)
            && in_array('mi_products_needing_cop', $availableTools, true)) {
            return 'mi_products_needing_cop';
        }

        if (preg_match('/\bhow\s+many\b.*\bmi\s+products?\b|\bcount\s+(?:the\s+)?mi\s+products?\b|\b(?:ilang|ilan)\b.*\b(?:mi\s+)?products?\b/i', $lastUserMessage)
            && in_array('mi_product_catalog_summary', $availableTools, true)) {
            return 'mi_product_catalog_summary';
        }

        if (preg_match('/\b(?:my\s+)?(?:pending\s+)?budget\s+requests?\b|\b(?:pending\s+)?budget\b.*\b(?:ko|akin)\b/i', $lastUserMessage)
            && in_array('my_mi_budget_requests', $availableTools, true)) {
            return 'my_mi_budget_requests';
        }

        if (preg_match('/\b(?:my\s+)?(?:pending\s+)?(?:travel\s+)?liquidations?\b|\b(?:status|pending|liquidation|liquidations)\b.*\b(?:ko|akin)\b/i', $lastUserMessage)
            && in_array('my_mi_liquidations', $availableTools, true)) {
            return 'my_mi_liquidations';
        }

        if (preg_match('/\b(budget|expense|forecast|project|delivery|inventory|liquidation|spend|spent|last\s+month)\b/i', $lastUserMessage)) {
            return null;
        }

        $asksForPrice = preg_match('/\b(how\s+much\s+(?:is|for)|price\s+(?:of|for)|selling\s+price\s+(?:of|for)|what\s+is\s+the\s+price\s+(?:of|for)|cost\s+of|magkano)\b/i', $lastUserMessage) === 1;
        $specificProductTerms = preg_replace('/\b(?:how\s+much\s+(?:is|for)|price\s+(?:of|for)|selling\s+price\s+(?:of|for)|what\s+is\s+the\s+price\s+(?:of|for)|cost\s+of|magkano(?:\s+(?:ba|ang|itong|ito|sa))?|the|a|an|item|product|this|that|it|please|naman|po|ba|itong)\b/i', ' ', $lastUserMessage);
        $specificProductTerms = trim(preg_replace('/[^\pL\pN]+/u', ' ', (string) $specificProductTerms));
        $hasKnownProducts = ! empty($context['verified_previous_products']);
        $priceFollowUp = $hasKnownProducts && preg_match('/\b(how\s+about|what\s+about|larger|large|smaller|small|another|other\s+one|the\s+next\s+one|mas\s+malaki|mas\s+maliit|yung\s+malaki|iyong\s+malaki)\b/i', $lastUserMessage) === 1;
        $priceToolAvailable = in_array('lookup_mi_product_prices', $availableTools, true);

        return $priceToolAvailable && (($asksForPrice && $specificProductTerms !== '') || $priceFollowUp)
            ? 'lookup_mi_product_prices'
            : null;
    }

    /** @param array<string, mixed> $context */
    private function instructions(Company $company, array $context, bool $canViewProductPrices, bool $canCreateQuotation): string
    {
        $conversation = config('ai_assistant.conversation', []);
        $priceGuidance = $canViewProductPrices
            ? 'The user is authorized to use the MI selling-price lookup when it is supplied.'
            : 'The user is not authorized to view MI selling prices; do not infer that a price is missing, and explain that price-view access is required.';
        $quotationGuidance = $canCreateQuotation
            ? 'The user is authorized to prepare a quotation; offer the existing quotation workflow as an optional next step. The user must explicitly confirm before a draft is saved.'
            : 'The user cannot currently create quotations; do not offer or imply access to quotation creation.';

        $moduleVoice = $conversation['module_voice'][$context['key'] ?? ''] ?? 'For this module, explain verified information in practical terms and adapt your level of detail to the user’s question.';
        $verifiedProductContext = ! empty($context['verified_previous_products'])
            ? 'Verified MI product results from earlier in this conversation: '.json_encode($context['verified_previous_products'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).'. Use these facts to resolve follow-up references. If the user asks about another size or variant, search using the product family and requested variant; do not assume a match.'
            : '';

        return implode(' ', array_filter([
            $conversation['personality'] ?? 'You are Project Tracker’s AI business assistant. Be friendly, professional, and concise.',
            $conversation['language'] ?? 'Reply in the language and style of the user’s latest message, including Filipino or Taglish when appropriate.',
            $conversation['conversation'] ?? 'Use recent conversation history to resolve follow-up questions and ask only concise clarifying questions when needed.',
            $conversation['proactivity'] ?? 'Offer a useful next step only when it is relevant, and confirm before consequential actions.',
            $conversation['safety'] ?? 'Never invent business facts or claim a tool succeeded when it failed. Respect server-enforced permissions and company isolation.',
            $moduleVoice,
            'The active company is '.$company->code.'. Use the page context to explain the current page, but use any supplied tool the user is authorized to access even if it belongs to another module in this company. Never claim access to another company. Use only supplied read-only tools for saved records. Page context contains route metadata only, never form values. Never create, edit, delete, submit, release, or approve records.',
            'For MI product price questions, call lookup_mi_product_prices when available; price access is independent from quotation creation. Use search_mi_products for product details without prices. '.$priceGuidance.' Report a price and SKU only when returned by the price tool. State PHP currency and MI product selling price basis. Never treat factory or purchase cost as selling price. If multiple similar products are returned, present distinct options; do not assume Round Table and Round Side Table are the same product.',
            $quotationGuidance,
            'Do not invent taxes, discounts, delivery fees, payment terms, forecasts, or unavailable data. If a data tool is unavailable, explain the user-facing limitation briefly without naming internal functions or claiming that a search was made. For COP, explain when tracking is unavailable.',
            $verifiedProductContext,
            'Authorized page context: '.json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]));
    }
}
