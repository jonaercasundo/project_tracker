<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use RuntimeException;

class AiAssistantService
{
    public function __construct(private AiProviderConfiguration $configuration, private OpenAiResponsesClient $client) {}

    /** @param array<int, array{role: string, content: string}> $history
     * @param  callable(string): void  $onDelta
     * @return array{model: string, request_id: ?string, input_tokens: ?int, output_tokens: ?int}
     */
    public function respond(User $user, Company $company, array $context, array $history, callable $onDelta): array
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
        $usage = ['model' => $model, 'request_id' => null, 'input_tokens' => null, 'output_tokens' => null];
        $emitted = '';

        for ($round = 0; $round < 3; $round++) {
            $request = $this->client->stream($key, [
                'model' => $model,
                'instructions' => $this->instructions($company, $context),
                'input' => $input,
                'tools' => $tools,
                'stream' => true,
                'store' => false,
                'max_output_tokens' => (int) config('ai_assistant.max_output_tokens', 1200),
            ]);

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
                $input[] = ['type' => 'function_call_output', 'call_id' => $call['call_id'], 'output' => json_encode($result, JSON_THROW_ON_ERROR)];
            }
        }

        throw new RuntimeException('The assistant reached its tool-call limit.');
    }

    /** @param array<string, mixed> $context */
    private function instructions(Company $company, array $context): string
    {
        return 'You are the Project Tracker assistant for the active company '.$company->code.'. Follow the authorized module registry and page context below. Never claim access to another company. Use only the supplied read-only tools for saved business records; never invent SQL or request arbitrary database access. Do not expose secrets or ask for passwords. Page context contains route metadata only, never form values. Never create, edit, delete, submit, release, or approve records. Explain workflows and calculations clearly, identify assumptions, and say when data is unavailable.\n\nAuthorized page context: '.json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
