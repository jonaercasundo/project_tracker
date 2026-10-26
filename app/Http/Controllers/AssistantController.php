<?php

namespace App\Http\Controllers;

use App\Models\AiActivity;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Company;
use App\Models\User;
use App\Services\AiAssistantService;
use App\Services\AiModuleRegistry;
use App\Services\AiPageContextService;
use App\Services\AiProviderConfiguration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AssistantController extends Controller
{
    public function workspace(Request $request): View
    {
        abort_unless($request->user()?->can('ai.assistant.use'), 403, 'You do not have permission to use the AI assistant.');

        return view('ai.workspace');
    }

    public function capabilities(Request $request, AiModuleRegistry $registry): JsonResponse
    {
        [$user, $company] = $this->identity($request);

        return response()->json(['context' => $registry->pageContext($user, $company, $request->string('page_route')->toString() ?: $request->route()?->getName())]);
    }

    public function conversations(Request $request): JsonResponse
    {
        [$user, $company] = $this->identity($request);
        $this->pruneExpired($user, $company);
        $conversations = AiConversation::query()->where('user_id', $user->getKey())->where('company_id', $company->getKey())
            ->withCount('messages')->orderByDesc('last_message_at')->limit(30)->get(['id', 'title', 'last_message_at']);

        return response()->json(['conversations' => $conversations]);
    }

    public function createConversation(Request $request): JsonResponse
    {
        [$user, $company] = $this->identity($request);
        $conversation = AiConversation::create(['user_id' => $user->getKey(), 'company_id' => $company->getKey(), 'title' => 'New conversation']);

        return response()->json(['conversation' => $conversation], 201);
    }

    public function showConversation(Request $request, AiConversation $conversation): JsonResponse
    {
        [$user, $company] = $this->authorizeConversation($request, $conversation);
        $this->authorizePriceHistory($user, $company, $conversation);
        $conversation->load(['messages' => fn ($query) => $query->latest('id')->limit((int) config('ai_assistant.message_limit', 20))]);
        $conversation->setRelation('messages', $conversation->messages->sortBy('id')->values());

        return response()->json(['conversation' => $conversation]);
    }

    public function deleteConversation(Request $request, AiConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);
        $conversation->delete();

        return response()->json(['deleted' => true]);
    }

    public function stream(Request $request, AiConversation $conversation, AiPageContextService $pageContext, AiAssistantService $assistant, AiProviderConfiguration $configuration): StreamedResponse
    {
        [$user, $company] = $this->authorizeConversation($request, $conversation);
        $this->authorizePriceHistory($user, $company, $conversation);
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:'.(int) config('ai_assistant.max_message_characters', 4000)],
            'page_route' => ['nullable', 'string', 'max:160'],
        ]);
        $text = trim($validated['message']);
        if ($text === '') {
            throw ValidationException::withMessages(['message' => 'Please enter a message.']);
        }
        abort_if($configuration->apiKey() === null, 503, 'OpenAI configuration is required before using the assistant.');
        abort_if($configuration->monthlyLimitReached(), 429, 'The system monthly AI token limit has been reached.');

        $context = $pageContext->current($request);
        $history = DB::transaction(function () use ($conversation, $text): array {
            $conversation->messages()->create(['role' => 'user', 'content' => $text]);
            if ($conversation->title === 'New conversation') {
                $conversation->title = Str::limit($text, 100);
            }
            $conversation->last_message_at = now();
            $conversation->save();

            return $conversation->messages()->latest('id')->limit((int) config('ai_assistant.message_limit', 20))->get()->reverse()
                ->map(fn (AiMessage $message): array => ['role' => $message->role, 'content' => $message->content])->values()->all();
        });

        return response()->stream(function () use ($assistant, $user, $company, $context, $history, $conversation): void {
            $answer = '';
            $productCards = [];
            $emit = static function (string $event, array $data): void {
                echo 'event: '.$event."\n".'data: '.json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            try {
                $usage = $assistant->respond($user, $company, $context, $history, function (string $delta) use (&$answer, $emit): void {
                    $answer .= $delta;
                    $emit('delta', ['text' => $delta]);
                }, function (array $products) use ($emit, &$productCards, $user, $company, $conversation): void {
                    $productCards = $products;
                    if ($company->code === 'MI' && $user->can('mi.quotation.create') && Schema::hasTable('ai_quotation_audits')) {
                        DB::table('ai_quotation_audits')->insert([
                            'conversation_id' => $conversation->getKey(), 'user_id' => $user->getKey(),
                            'company_id' => $company->getKey(), 'action' => 'quotation_suggestion_shown',
                            'metadata' => json_encode(['item_count' => count($products)], JSON_THROW_ON_ERROR),
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                    $emit('products', ['items' => $products]);
                });
                DB::transaction(function () use ($conversation, $answer, $usage, $user, $company, &$productCards): void {
                    $assistantMessage = ['role' => 'assistant', 'content' => $answer, 'model' => $usage['model'], 'input_tokens' => $usage['input_tokens'], 'output_tokens' => $usage['output_tokens']];
                    if (Schema::hasColumn('ai_messages', 'metadata')) {
                        $assistantMessage['metadata'] = $productCards === [] ? null : ['products' => $productCards];
                    }
                    $conversation->messages()->create($assistantMessage);
                    $conversation->forceFill(['last_message_at' => now()])->save();
                    AiActivity::create(['user_id' => $user->getKey(), 'company_id' => $company->getKey(), 'conversation_id' => $conversation->getKey(), ...$usage, 'status' => 'completed']);
                });
                $emit('done', ['ok' => true]);
            } catch (Throwable $exception) {
                report($exception);
                AiActivity::create(['user_id' => $user->getKey(), 'company_id' => $company->getKey(), 'conversation_id' => $conversation->getKey(), 'status' => 'failed']);
                $emit('error', ['message' => 'The assistant could not complete this response. Please try again.']);
            }
        }, 200, ['Content-Type' => 'text/event-stream', 'Cache-Control' => 'no-cache, no-transform', 'X-Accel-Buffering' => 'no', 'Connection' => 'keep-alive']);
    }

    /** @return array{0: User, 1: Company} */
    private function identity(Request $request): array
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless($user->can('ai.assistant.use'), 403, 'You do not have permission to use the AI assistant.');
        $company = $user->currentCompany();
        abort_unless($company instanceof Company && $user->belongsToCompany($company->getKey()), 403, 'Select an active company before using the assistant.');
        abort_unless(app(AiProviderConfiguration::class)->isEnabled(), 503, 'The AI assistant is currently disabled.');

        return [$user, $company];
    }

    /** @return array{0: User, 1: Company} */
    private function authorizeConversation(Request $request, AiConversation $conversation): array
    {
        [$user, $company] = $this->identity($request);
        abort_unless($conversation->user_id === $user->getKey() && $conversation->company_id === $company->getKey(), 404);
        if ($conversation->updated_at?->lt(now()->subDays(max(1, (int) config('ai_assistant.retention_days', 90))))) {
            $conversation->delete();
            abort(404);
        }

        return [$user, $company];
    }

    private function authorizePriceHistory(User $user, Company $company, AiConversation $conversation): void
    {
        if ($company->code === 'MI' && ! $user->can('mi.product-prices.view') && Schema::hasColumn('ai_messages', 'metadata')) {
            abort_if($conversation->messages()->whereNotNull('metadata')->exists(), 403, 'You no longer have permission to view MI product price data in this conversation.');
        }
    }

    private function pruneExpired(User $user, Company $company): void
    {
        $cutoff = now()->subDays(max(1, (int) config('ai_assistant.retention_days', 90)));
        AiConversation::query()->where('user_id', $user->getKey())->where('company_id', $company->getKey())->where('updated_at', '<', $cutoff)->delete();
    }
}
