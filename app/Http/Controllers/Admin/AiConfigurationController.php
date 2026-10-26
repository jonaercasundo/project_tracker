<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiActivity;
use App\Models\AiProviderSetting;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\AiProviderConfiguration;
use App\Services\OpenAiResponsesClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AiConfigurationController extends Controller
{
    public function index(AiProviderConfiguration $configuration): View
    {
        $setting = $configuration->setting();
        $usage = AiActivity::query()->where('status', 'completed')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('COUNT(*) as request_count, COALESCE(SUM(COALESCE(input_tokens, 0)), 0) as input_tokens, COALESCE(SUM(COALESCE(output_tokens, 0)), 0) as output_tokens')
            ->first();
        $lastCredentialChangeId = DB::table('ai_provider_setting_audits')->whereIn('action', ['key_replaced', 'key_removed'])->max('id') ?? 0;
        $lastTestAudit = DB::table('ai_provider_setting_audits')->where('action', 'connection_test')->orderByDesc('id')->first(['id', 'metadata', 'created_at']);
        $lastTestMetadata = $lastTestAudit ? json_decode($lastTestAudit->metadata, true) : [];
        $lastSuccessfulAudit = DB::table('ai_provider_setting_audits')
            ->where('action', 'connection_test')
            ->where('id', '>', $lastCredentialChangeId)
            ->where('metadata->status', 'success')
            ->orderByDesc('id')->first(['created_at']);
        $lastSuccessfulTestAt = $lastSuccessfulAudit ? Carbon::parse($lastSuccessfulAudit->created_at) : null;

        return view('admin.ai-configuration', [
            'provider' => 'OpenAI',
            'model' => $configuration->model(),
            'supportedModels' => $configuration->supportedModels(),
            'enabled' => $configuration->isEnabled(),
            'credentialSource' => $configuration->credentialSource(),
            'hasDatabaseKey' => $configuration->hasDatabaseKey(),
            'environmentFallbackConfigured' => $configuration->credentialSource() === 'environment',
            'perUserRequestLimit' => $configuration->perUserRequestLimit(),
            'monthlyTokenLimit' => $configuration->monthlyTokenLimit(),
            'monthlyTokenUsage' => $configuration->monthlyTokenUsage(),
            'configuredBy' => $setting?->configured_by ? User::query()->whereKey($setting->configured_by)->value('name') : null,
            'lastUpdatedAt' => $setting?->updated_at,
            'lastTestStatus' => (int) ($lastTestAudit->id ?? 0) > (int) $lastCredentialChangeId ? ($lastTestMetadata['status'] ?? null) : 'not_tested',
            'lastTestMessage' => (int) ($lastTestAudit->id ?? 0) > (int) $lastCredentialChangeId ? ($lastTestMetadata['message'] ?? null) : null,
            'lastTestCredentialSource' => (int) ($lastTestAudit->id ?? 0) > (int) $lastCredentialChangeId ? ($lastTestMetadata['credential_source'] ?? null) : null,
            'lastSuccessfulTestAt' => $lastSuccessfulTestAt,
            'usage' => $usage,
        ]);
    }

    public function store(Request $request, AiProviderConfiguration $configuration, AdminAuditLogger $audit): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'openai_api_key' => ['nullable', 'string', 'max:255'],
            'model' => ['required', 'string', Rule::in($configuration->supportedModels())],
            'enabled' => ['required', 'boolean'],
            'per_user_requests_per_minute' => ['required', 'integer', 'between:1,120'],
            'monthly_token_limit' => ['nullable', 'integer', 'between:1,1000000000000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Please review the AI configuration fields.', 'errors' => $validator->errors()], 422)
                ->header('Cache-Control', 'no-store');
        }

        $data = $validator->validated();
        $submittedKey = trim((string) ($data['openai_api_key'] ?? ''));
        $user = $request->user();
        $existing = $configuration->setting();
        $before = $this->safeSnapshot($existing);
        $setting = $existing ?? new AiProviderSetting(['id' => 1]);
        $keyWasReplaced = $submittedKey !== '';

        DB::transaction(function () use ($setting, $data, $submittedKey, $keyWasReplaced, $user, $before, $audit): void {
            $setting->provider = 'openai';
            $setting->model = $data['model'];
            $setting->enabled = (bool) $data['enabled'];
            $setting->per_user_requests_per_minute = (int) $data['per_user_requests_per_minute'];
            $setting->monthly_token_limit = isset($data['monthly_token_limit']) && $data['monthly_token_limit'] !== null
                ? (int) $data['monthly_token_limit']
                : null;
            $setting->configured_by = $user->getAuthIdentifier();
            if ($keyWasReplaced) {
                $setting->api_key = $submittedKey;
            }
            $setting->save();

            $this->audit($user->getAuthIdentifier(), $keyWasReplaced ? 'key_replaced' : 'settings_updated', [
                'before' => $before,
                'after' => $this->safeSnapshot($setting),
            ]);
            $audit->record($user, $keyWasReplaced ? 'ai.configuration_key_replaced' : 'ai.configuration_updated', 'ai_provider_setting', 1, 'Updated OpenAI assistant configuration.', $user->currentCompany()?->getKey(), [
                'model' => $data['model'],
                'enabled' => (bool) $data['enabled'],
                'credential_replaced' => $keyWasReplaced,
            ]);
        });

        return response()->json(['message' => 'AI configuration saved. The API key is stored encrypted and will not be shown again.'])
            ->header('Cache-Control', 'no-store');
    }

    public function removeKey(Request $request, AiProviderConfiguration $configuration, AdminAuditLogger $audit): JsonResponse
    {
        $setting = $configuration->setting();
        $before = $this->safeSnapshot($setting);
        if ($setting === null || ! $configuration->hasDatabaseKey()) {
            return response()->json(['message' => $configuration->credentialSource() === 'environment'
                ? 'No database key is stored. The environment fallback remains configured.'
                : 'No database API key is stored.'])
                ->header('Cache-Control', 'no-store');
        }

        DB::transaction(function () use ($setting, $request, $before, $audit): void {
            $setting->api_key = null;
            $setting->configured_by = $request->user()->getAuthIdentifier();
            $setting->save();
            $this->audit($request->user()->getAuthIdentifier(), 'key_removed', [
                'before' => $before,
                'after' => $this->safeSnapshot($setting),
            ]);
            $audit->record($request->user(), 'ai.configuration_key_removed', 'ai_provider_setting', 1, 'Removed the saved OpenAI credential.', $request->user()->currentCompany()?->getKey());
        });

        return response()->json(['message' => $configuration->credentialSource() === 'environment'
            ? 'The database API key was removed. The environment fallback is still active.'
            : 'The database API key was removed. Add a key before using OpenAI.'])
            ->header('Cache-Control', 'no-store');
    }

    public function testConnection(Request $request, AiProviderConfiguration $configuration, OpenAiResponsesClient $client, AdminAuditLogger $audit): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'openai_api_key' => ['nullable', 'string', 'max:255'],
            'model' => ['required', 'string', Rule::in($configuration->supportedModels())],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Please review the connection test fields.', 'errors' => $validator->errors()], 422)
                ->header('Cache-Control', 'no-store');
        }

        $data = $validator->validated();
        $submittedKey = trim((string) ($data['openai_api_key'] ?? ''));
        $apiKey = $submittedKey !== '' ? $submittedKey : $configuration->apiKey();
        if ($apiKey === null) {
            return response()->json(['status' => 'configuration_required', 'message' => 'Save an OpenAI API key or configure the environment fallback before testing.'], 422)
                ->header('Cache-Control', 'no-store');
        }

        $result = $client->testConnection($apiKey, $data['model']);
        $testedAt = $result['status'] === 'success' ? now() : null;
        $this->audit($request->user()->getAuthIdentifier(), 'connection_test', [
            'status' => $result['status'],
            'message' => $result['message'],
            'model' => $data['model'],
            'credential_source' => $submittedKey !== '' ? 'submitted' : $configuration->credentialSource(),
        ]);
        $audit->record($request->user(), 'ai.connection_tested', 'ai_provider_setting', 1, 'Tested the OpenAI connection: '.$result['status'].'.', $request->user()->currentCompany()?->getKey(), [
            'status' => $result['status'],
            'model' => $data['model'],
            'credential_source' => $submittedKey !== '' ? 'submitted' : $configuration->credentialSource(),
        ]);

        return response()->json([...$result, 'tested_at' => $testedAt?->toIso8601String()], $result['status'] === 'success' ? 200 : 422)
            ->header('Cache-Control', 'no-store');
    }

    /** @return array<string, mixed> */
    private function safeSnapshot(?AiProviderSetting $setting): array
    {
        return [
            'provider' => 'openai',
            'has_database_key' => $setting !== null && (string) $setting->getRawOriginal('api_key') !== '',
            'model' => $setting?->model,
            'enabled' => $setting?->enabled,
            'per_user_requests_per_minute' => $setting?->per_user_requests_per_minute,
            'monthly_token_limit' => $setting?->monthly_token_limit,
        ];
    }

    /** @param array<string, mixed> $metadata */
    private function audit(?int $userId, string $action, array $metadata): void
    {
        DB::table('ai_provider_setting_audits')->insert([
            'performed_by' => $userId,
            'action' => $action,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
