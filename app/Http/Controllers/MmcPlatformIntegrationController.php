<?php

namespace App\Http\Controllers;

use App\Models\MmcPlatformSetting;
use App\Services\AdminAuditLogger;
use App\Services\MmcPlatformApiClient;
use App\Services\MmcPlatformConfiguration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorContract;

class MmcPlatformIntegrationController extends Controller
{
    public function index(MmcPlatformApiClient $client): View
    {
        return view('integrations.mmc-platform', [
            'connection' => $client->configurationStatus(),
            'testResult' => session('mmc_platform_test'),
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

        return to_route('integrations.mmc-platform.index')
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
}
