<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMmcBiometricConnectionRequest;
use App\Models\MmcBiometricConnection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;

class MmcBiometricConnectionController extends Controller
{
    public function index(): View
    {
        $storageAvailable = Schema::hasTable('mmc_biometric_connections');
        $connection = $storageAvailable
            ? MmcBiometricConnection::query()->whereKey('MMC')->first()
            : null;

        return view('hr.biometric-connection.index', [
            'connection' => $connection,
            'storageAvailable' => $storageAvailable,
        ]);
    }

    public function update(UpdateMmcBiometricConnectionRequest $request): RedirectResponse
    {
        if (! Schema::hasTable('mmc_biometric_connections')) {
            return redirect()->route('hr.biometric.index')->with('error', 'Apply the biometric connection migration before saving device details.');
        }

        $details = $request->validated();
        if (blank($details['comm_key'] ?? null)) {
            unset($details['comm_key']);
        }

        MmcBiometricConnection::query()->updateOrCreate(['id' => 'MMC'], $details);

        return redirect()->route('hr.biometric.index')->with('success', 'Biometric connection details saved.');
    }
}
