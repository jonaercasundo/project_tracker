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
        if (filled($details['password'] ?? null)) {
            $details['comm_key'] = $details['password'];
        }
        unset($details['password']);

        MmcBiometricConnection::query()->updateOrCreate(['id' => 'MMC'], $details);

        return redirect()->route('hr.biometric.index')->with('success', 'Biometric connection details saved.');
    }

    public function test(UpdateMmcBiometricConnectionRequest $request): RedirectResponse
    {
        $details = $request->validated();
        $errorCode = 0;
        $errorMessage = '';
        $socket = @fsockopen(
            $details['device_ip'],
            (int) $details['tcp_port'],
            $errorCode,
            $errorMessage,
            3,
        );

        if ($socket === false) {
            return redirect()->route('hr.biometric.index')
                ->withInput($request->safe()->except('password')->all())
                ->with('connection_test', [
                    'status' => 'failure',
                    'message' => "The Tracker server could not reach {$details['device_ip']}:{$details['tcp_port']}. Check the G1 network settings, power, and firewall.",
                ]);
        }

        fclose($socket);

        return redirect()->route('hr.biometric.index')
            ->withInput($request->safe()->except('password')->all())
            ->with('connection_test', [
                'status' => 'success',
                'message' => "TCP port {$details['tcp_port']} on {$details['device_ip']} is reachable from the Tracker server. The Comm Key and device protocol were not checked.",
            ]);
    }
}
