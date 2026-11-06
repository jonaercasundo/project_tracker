<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MmcPlatformApiClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class MmcPlatformIntegrationController extends Controller
{
    public function index(MmcPlatformApiClient $client): View
    {
        return view('admin.mmc-platform-integration', [
            'connection' => $client->configurationStatus(),
        ]);
    }

    public function testConnection(MmcPlatformApiClient $client): RedirectResponse
    {
        $result = $client->testConnection();

        return back()->with($result['status'] === 'success' ? 'success' : 'error', $result['message']);
    }
}
