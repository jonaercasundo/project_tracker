<?php

namespace App\Http\Controllers;

use App\Jobs\SyncMmcPlatformProject;
use App\Models\MmcPlatformSetting;
use App\Services\AdminAuditLogger;
use App\Services\MmcPlatformApiClient;
use App\Services\MmcPlatformConfiguration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

        $companyId = (int) $request->user()->currentCompany()->getKey();
        $projects = Schema::hasTable('projects')
            ? DB::table('projects')
                ->when(Schema::hasColumn('projects', 'company_id'), function ($query) use ($companyId): void {
                    $query->whereNull('company_id')->orWhere('company_id', $companyId);
                })
                ->orderBy('project_name')
                ->get(['project_id', 'project_name', 'project_code', 'ref_no'])
            : collect();

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
            'projects' => $projects,
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

    public function syncProject(Request $request): JsonResponse
    {
        $companyId = (int) $request->user()->currentCompany()->getKey();
        abort_unless(Schema::hasTable('projects'), 503, 'The Tracker projects table is unavailable.');
        $trackerProjectRule = Rule::exists('projects', 'project_id');
        if (Schema::hasColumn('projects', 'company_id')) {
            $trackerProjectRule->where(fn ($query) => $query->where(fn ($query) => $query->whereNull('company_id')->orWhere('company_id', $companyId)));
        }

        $data = $request->validate([
            'program_id' => ['required', 'string', 'max:255'],
            'tracker_project_id' => ['required', 'integer', $trackerProjectRule],
            'program_name' => ['nullable', 'string', 'max:255'],
            'program_info' => ['nullable', 'array:id,name,project_title,fiscal_year,status'],
            'program_info.id' => ['nullable', 'string', 'max:255'],
            'program_info.name' => ['nullable', 'string', 'max:255'],
            'program_info.project_title' => ['nullable', 'string', 'max:255'],
            'program_info.fiscal_year' => ['nullable', 'integer'],
            'program_info.status' => ['nullable', 'string', 'max:100'],
        ]);

        if (! Schema::hasTable('mmc_platform_project_syncs')
            || ! Schema::hasTable('mmc_platform_masterlist_records')
            || ! Schema::hasColumn('mmc_platform_project_syncs', 'tracker_project_id')) {
            return response()->json(['status' => 'failed', 'message' => 'Run the MMC Platform masterlist sync migrations before syncing data.'], 503);
        }

        $userId = (int) $request->user()->getKey();
        $syncId = (string) Str::uuid();
        $statusKey = SyncMmcPlatformProject::statusKey($companyId, $userId, $syncId);

        Cache::put($statusKey, ['status' => 'pending'], now()->addHours(2));

        try {
            Bus::dispatch(new SyncMmcPlatformProject($statusKey, $companyId, $data));
        } catch (\Throwable $exception) {
            Cache::forget($statusKey);
            report($exception);

            return response()->json(['status' => 'failed', 'message' => 'Tracker could not queue the sync. Please try again.'], 503);
        }

        return response()->json([
            'status' => 'pending',
            'status_url' => route('integrations.mmc-platform.sync-status', ['syncId' => $syncId]),
        ], 202);
    }

    public function syncStatus(Request $request, string $syncId): JsonResponse
    {
        $statusKey = SyncMmcPlatformProject::statusKey(
            (int) $request->user()->currentCompany()->getKey(),
            (int) $request->user()->getKey(),
            $syncId,
        );
        $result = Cache::get($statusKey);

        return response()->json(
            $result ?? ['status' => 'failed', 'message' => 'This sync status is no longer available.'],
            $result === null ? 404 : 200,
            ['Cache-Control' => 'no-store'],
        );
    }
}
