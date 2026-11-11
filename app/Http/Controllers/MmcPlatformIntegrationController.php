<?php

namespace App\Http\Controllers;

use App\Models\MmcPlatformSetting;
use App\Services\AdminAuditLogger;
use App\Services\MmcPlatformApiClient;
use App\Services\MmcPlatformConfiguration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorContract;

class MmcPlatformIntegrationController extends Controller
{
    public function index(Request $request, MmcPlatformApiClient $client): View
    {
        $tabs = ['overview', 'integration', 'partners', 'api-webhooks', 'reconciliation', 'audit-logs'];
        $requestedTab = $request->query('tab', 'overview');
        $tab = is_string($requestedTab) && in_array($requestedTab, $tabs, true) ? $requestedTab : 'overview';
        $auditRows = null;
        if ($tab === 'audit-logs' && Schema::hasTable('admin_activity_logs')) {
            $auditRows = DB::table('admin_activity_logs')
                ->where('event', 'integrations.mmc_platform_configuration_updated')
                ->where('company_id', $request->user()->currentCompany()?->getKey())
                ->orderByDesc('id')
                ->select(['id', 'event', 'summary', 'actor_user_id', 'created_at'])
                ->paginate(15)
                ->withQueryString();
        }

        return view('integrations.mmc-platform', [
            'connection' => $client->configurationStatus(),
            'testResult' => session('mmc_platform_test'),
            'tab' => $tab,
            'tabs' => [
                'overview' => 'Overview',
                'integration' => 'Integration',
                'partners' => 'Partners',
                'api-webhooks' => 'API & Webhooks',
                'reconciliation' => 'Reconciliation',
                'audit-logs' => 'Audit Logs',
            ],
            'auditRows' => $auditRows,
            'auditStorageAvailable' => Schema::hasTable('admin_activity_logs'),
        ]);
    }

    public function store(Request $request, MmcPlatformConfiguration $configuration, MmcPlatformApiClient $client, AdminAuditLogger $audit): RedirectResponse
    {
        if (! Schema::hasTable('mmc_platform_settings')) {
            return back()->with('error', 'Run the MMC Platform settings migration before saving configuration.');
        }

        $validator = Validator::make($request->all(), [
            'base_url' => ['required', 'string', 'max:2048'],
            'read_key' => ['nullable', 'string', 'max:4096'],
        ]);
        $validator->after(function (ValidatorContract $validator) use ($request, $configuration, $client): void {
            $urlError = $client->baseUrlError(trim((string) $request->input('base_url')));
            if ($urlError !== null) {
                $validator->errors()->add('base_url', $urlError);
            }

            $submittedKey = trim((string) $request->input('read_key'));
            $current = $configuration->current();
            if ($submittedKey === '' && $current['read_key'] === '') {
                $validator->errors()->add('read_key', 'Enter an MMC read-only API key beginning with mmc_.');
            } elseif ($submittedKey !== '' && ! str_starts_with($submittedKey, 'mmc_')) {
                $validator->errors()->add('read_key', 'The API key must begin with mmc_.');
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->except('read_key'));
        }

        $data = $validator->validated();
        $setting = MmcPlatformSetting::query()->firstOrNew(['id' => 1]);
        $setting->base_url = rtrim(trim($data['base_url']), '/');
        $submittedKey = trim((string) ($data['read_key'] ?? ''));
        if ($submittedKey !== '') {
            $setting->read_key = $submittedKey;
        }
        $setting->configured_by = $request->user()->getAuthIdentifier();
        $setting->save();

        $audit->record($request->user(), 'integrations.mmc_platform_configuration_updated', 'mmc_platform_setting', 1, 'Updated MMC Platform read connection settings.', $request->user()->currentCompany()?->getKey(), [
            'base_url' => $setting->base_url,
            'credential_replaced' => $submittedKey !== '',
        ]);

        return to_route('integrations.mmc-platform.index', ['tab' => 'integration'])
            ->with('success', 'MMC Platform configuration saved. The API key is encrypted and will not be shown again.');
    }

    public function testConnection(Request $request, MmcPlatformApiClient $client): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'base_url' => ['required', 'string', 'max:2048'],
            'read_key' => ['nullable', 'string', 'max:4096'],
        ]);
        $validator->after(function (ValidatorContract $validator) use ($request, $client): void {
            $urlError = $client->baseUrlError(trim((string) $request->input('base_url')));
            if ($urlError !== null) {
                $validator->errors()->add('base_url', $urlError);
            }

            $submittedKey = trim((string) $request->input('read_key'));
            if ($submittedKey !== '' && ! str_starts_with($submittedKey, 'mmc_')) {
                $validator->errors()->add('read_key', 'The API key must begin with mmc_.');
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->except('read_key'));
        }

        $result = $client->testConnection(
            trim($request->string('base_url')->toString()),
            trim($request->string('read_key')->toString()),
        );

        return back()->with('mmc_platform_test', $result);
    }

    public function programs(MmcPlatformApiClient $client): JsonResponse
    {
        return response()->json($client->getPrograms());
    }

    public function checkConfiguredConnection(MmcPlatformApiClient $client): JsonResponse
    {
        return response()->json($client->testConnection());
    }

    public function previewData(Request $request, MmcPlatformApiClient $client): JsonResponse
    {
        $data = $request->validate([
            'program_id' => ['required', 'string', 'max:255'],
            'program_name' => ['nullable', 'string', 'max:255'],
            'limit' => ['required', 'integer', 'min:1', 'max:2000'],
        ]);

        return response()->json($client->previewMasterList($data['program_id'], $data['program_name'] ?? '', (int) $data['limit']));
    }

    public function syncProject(Request $request, MmcPlatformApiClient $client): JsonResponse
    {
        $data = $request->validate([
            'program_id' => ['required', 'string', 'max:255'],
            'program_name' => ['nullable', 'string', 'max:255'],
            'program_info' => ['nullable', 'array:id,name,project_title,fiscal_year,status'],
            'program_info.id' => ['nullable', 'string', 'max:255'],
            'program_info.name' => ['nullable', 'string', 'max:255'],
            'program_info.project_title' => ['nullable', 'string', 'max:255'],
            'program_info.fiscal_year' => ['nullable', 'integer'],
            'program_info.status' => ['nullable', 'string', 'max:100'],
        ]);

        if (! Schema::hasTable('mmc_platform_project_syncs')) {
            return response()->json(['status' => 'failed', 'message' => 'Run the MMC Platform project sync migration before syncing data.'], 503);
        }

        try {
            $result = $client->fetchProjectMasterList($data['program_id']);
            $records = $result['records'];
            $entities = $this->groupSyncedEntities($records);
            $companyId = (int) $request->user()->currentCompany()->getKey();
            $syncedAt = now();

            DB::transaction(function () use ($companyId, $data, $records, $entities, $syncedAt): void {
                DB::table('mmc_platform_project_syncs')->updateOrInsert(
                    ['company_id' => $companyId, 'program_id' => $data['program_id']],
                    [
                        'program_name' => $data['program_name'] ?? null,
                        'program_info' => json_encode($data['program_info'] ?? [], JSON_THROW_ON_ERROR),
                        'records' => json_encode(['master_list' => $records, 'entities' => $entities], JSON_THROW_ON_ERROR),
                        'entity_counts' => json_encode(array_map('count', $entities), JSON_THROW_ON_ERROR),
                        'record_count' => count($records),
                        'synced_at' => $syncedAt,
                        'updated_at' => $syncedAt,
                        'created_at' => $syncedAt,
                    ],
                );
            });

            return response()->json([
                'status' => 'success',
                'message' => 'MMC project data saved to Tracker.',
                'record_count' => count($records),
                'entity_counts' => array_map('count', $entities),
                'pages' => $result['pages'],
                'synced_at' => $syncedAt->toIso8601String(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'failed',
                'message' => $exception instanceof \RuntimeException || $exception instanceof \UnexpectedValueException
                    ? $exception->getMessage()
                    : 'The project data could not be saved. Please try again.',
            ], 502);
        }
    }

    /** @param array<int, array<string, mixed>> $records
     * @return array<string, array<int, mixed>>
     */
    private function groupSyncedEntities(array $records): array
    {
        $entities = ['schools' => [], 'lots' => [], 'keystages' => [], 'items' => [], 'packages' => []];
        $seen = ['schools' => [], 'lots' => [], 'keystages' => [], 'items' => [], 'packages' => []];

        foreach ($records as $record) {
            if (isset($record['school_id'])) {
                $entities['schools'][] = $record;
            }
            if (isset($record['lot_id'])) {
                $entities['lots'][] = $record;
            }
            foreach (['keystages', 'key_stages', 'key_stages_data', 'grade_levels'] as $key) {
                foreach ((array) ($record[$key] ?? []) as $stage) {
                    if (is_array($stage)) {
                        $entities['keystages'][] = $stage;
                    }
                }
            }
            foreach ((array) ($record['lines'] ?? []) as $item) {
                if (is_array($item)) {
                    $entities['items'][] = $item;
                    foreach ((array) ($item['packages'] ?? $item['package'] ?? []) as $package) {
                        if (is_array($package)) {
                            $entities['packages'][] = $package;
                        }
                    }
                }
            }
            foreach ((array) ($record['packages'] ?? $record['package'] ?? []) as $package) {
                if (is_array($package)) {
                    $entities['packages'][] = $package;
                }
            }
        }

        foreach ($entities as $type => $rows) {
            foreach ($rows as $row) {
                $key = match ($type) {
                    'schools' => $row['school_id'] ?? $row['id'] ?? null,
                    'lots' => $row['lot_id'] ?? $row['id'] ?? null,
                    'items' => $row['item_id'] ?? $row['item_code'] ?? $row['id'] ?? null,
                    'packages' => $row['package_id'] ?? $row['id'] ?? null,
                    default => $row['id'] ?? $row['key_stage_id'] ?? $row['name'] ?? null,
                };
                if ($key !== null) {
                    $seen[$type][(string) $key] = $row;
                }
            }
            if ($seen[$type] !== []) {
                $entities[$type] = array_values($seen[$type]);
            }
        }

        return $entities;
    }
}
