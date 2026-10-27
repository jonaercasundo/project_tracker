<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoogleSheetsSetting;
use App\Services\AdminAuditLogger;
use App\Services\GoogleSheetsApiClient;
use App\Services\GoogleSheetsBillingConfiguration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorContract;
use Throwable;

class GoogleSheetsSettingsController extends Controller
{
    public function index(GoogleSheetsBillingConfiguration $configuration, GoogleSheetsApiClient $client): View
    {
        $settings = $configuration->current();
        $credentials = $configuration->credentials();

        return view('billing.google-sheets-settings', [
            'settings' => [
                'enabled' => $settings['enabled'],
                'spreadsheet_id' => $settings['spreadsheet_id'],
                'sheet_tab' => $settings['sheet_tab'],
                'credential_source' => $settings['credential_source'],
                'has_saved_settings' => $settings['has_saved_settings'],
                'has_credentials' => $credentials !== null,
                'has_saved_credentials' => $settings['has_saved_credentials'],
            ],
            'connection' => $client->configurationStatus(),
        ]);
    }

    public function store(Request $request, AdminAuditLogger $audit): RedirectResponse|JsonResponse
    {
        if (! Schema::hasTable('google_sheets_settings')) {
            return back()->with('error', 'The Google Sheets settings migration must be applied before settings can be saved.');
        }

        $validator = Validator::make($request->all(), [
            'spreadsheet_id' => ['required', 'string', 'max:200', 'regex:/\A[A-Za-z0-9_-]{10,200}\z/'],
            'sheet_tab' => ['required', 'string', 'max:100'],
            'service_account_json' => ['nullable', 'string', 'max:60000'],
            'enabled' => ['required', 'boolean'],
        ]);
        $validator->after(function (ValidatorContract $validator) use ($request): void {
            $credentials = trim((string) $request->input('service_account_json', ''));
            if ($credentials === '') {
                return;
            }

            try {
                $decoded = json_decode($credentials, true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                $validator->errors()->add('service_account_json', 'Credentials must be valid service account JSON.');

                return;
            }

            if (! is_array($decoded)
                || ! is_string($decoded['client_email'] ?? null)
                || ! filter_var($decoded['client_email'], FILTER_VALIDATE_EMAIL)
                || ! is_string($decoded['private_key'] ?? null)
                || trim($decoded['private_key']) === '') {
                $validator->errors()->add('service_account_json', 'Credentials must include a valid client_email and private_key.');
            }
        });
        $validator->after(function (ValidatorContract $validator) use ($request): void {
            $tab = (string) $request->input('sheet_tab', '');
            if (preg_match('/[\x00-\x1F\x7F]/', $tab) === 1) {
                $validator->errors()->add('sheet_tab', 'Worksheet name cannot contain control characters.');
            }
        });

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please review the Google Sheets settings.', 'errors' => $validator->errors()], 422)
                    ->header('Cache-Control', 'no-store');
            }

            return back()->withErrors($validator);
        }

        $data = $validator->validated();
        $setting = GoogleSheetsSetting::query()->firstOrNew(['id' => 1]);
        $submittedCredentials = trim((string) ($data['service_account_json'] ?? ''));
        $setting->spreadsheet_id = trim($data['spreadsheet_id']);
        $setting->sheet_tab = trim($data['sheet_tab']);
        $setting->enabled = (bool) $data['enabled'];
        $setting->configured_by = $request->user()->getAuthIdentifier();
        if ($submittedCredentials !== '') {
            $setting->service_account_json = $submittedCredentials;
        }
        $setting->save();

        $audit->record($request->user(), 'billing.google_sheets_settings_updated', 'google_sheets_setting', 1, 'Updated billing Google Sheets connection settings.', $request->user()->currentCompany()?->getKey(), [
            'spreadsheet_id' => $setting->spreadsheet_id,
            'sheet_tab' => $setting->sheet_tab,
            'enabled' => $setting->enabled,
            'credential_replaced' => $submittedCredentials !== '',
        ]);

        return redirect()->route('billing.google-sheets-settings')->with('success', 'Google Sheets settings saved. Credentials are encrypted and remain hidden.');
    }

    public function testConnection(GoogleSheetsApiClient $client): RedirectResponse
    {
        $result = $client->testConnection();

        return back()->with($result['status'] === 'success' ? 'success' : 'error', $result['message']);
    }
}
