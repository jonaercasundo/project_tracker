<?php

namespace App\Http\Controllers;

use App\Services\MmcPlatformApiClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class MmcPlatformIntegrationController extends Controller
{
    public function index(MmcPlatformApiClient $client): View
    {
        return view('integrations.mmc-platform', [
            'connection' => $client->configurationStatus(),
        ]);
    }

    public function testConnection(MmcPlatformApiClient $client): RedirectResponse
    {
        $result = $client->testConnection();

        return back()->with($result['status'] === 'success' ? 'success' : 'error', $result['message']);
    }
}
