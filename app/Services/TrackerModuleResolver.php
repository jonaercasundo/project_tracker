<?php

namespace App\Services;

use Illuminate\Http\Request;

class TrackerModuleResolver
{
    /** @return array<int, string> */
    public function forRequest(Request $request): array
    {
        $path = strtolower(trim($request->path(), '/'));
        $routeName = (string) $request->route()?->getName();
        $subject = strtolower($routeName.' '.$path);

        if (str_contains($subject, 'return')) {
            return ['deliveries', 'returns'];
        }

        if (str_contains($subject, 'billing') || str_starts_with($path, 'billing/')) {
            return ['billing'];
        }

        if (str_contains($subject, 'inventory') || str_contains($subject, 'warehouse') || str_contains($subject, 'asset')) {
            return ['inventory'];
        }

        if (str_contains($subject, 'assistant')) {
            return ['assistant'];
        }

        if (str_contains($subject, 'taxonomy') || str_contains($subject, 'mi_') || str_contains($subject, 'mi.') || str_contains($subject, 'mi-app')) {
            return ['mi', 'accounting'];
        }

        if (str_contains($subject, 'budget') || str_contains($subject, 'liquidation') || str_contains($subject, 'accounting')) {
            return ['mi', 'accounting'];
        }

        if (str_contains($subject, 'finance') || str_contains($subject, 'ppl')) {
            return str_contains($subject, 'project') || str_contains($subject, 'bidding')
                ? ['finance', 'projects']
                : ['finance'];
        }

        if (str_contains($subject, 'delivery') || str_contains($subject, 'logistics') || str_contains($subject, 'receive-delivery')) {
            return ['deliveries'];
        }

        if (str_contains($subject, 'school') || str_contains($subject, 'project') || str_contains($subject, 'lifecycle') || str_contains($subject, 'bidding')) {
            return ['projects', 'deliveries'];
        }

        if (str_contains($subject, 'operations') || str_contains($subject, 'entry')) {
            return ['operations', 'projects', 'deliveries'];
        }

        if (str_contains($subject, 'user') || str_contains($subject, 'role') || str_contains($subject, 'company') || str_contains($subject, 'admin') || str_contains($subject, 'profile') || str_contains($subject, 'password') || str_contains($subject, 'integration')) {
            return ['administration'];
        }

        if (str_contains($subject, 'item')) {
            return ['inventory'];
        }

        return [];
    }

    public function isSuccessfulMutation(Request $request, int $statusCode): bool
    {
        if (in_array(strtoupper($request->method()), ['GET', 'HEAD', 'OPTIONS'], true) || $statusCode >= 400) {
            return false;
        }

        if ($request->hasSession() && $request->session()->has('errors')) {
            return false;
        }

        if ($request->routeIs('deliveries.monitoring.sync-billing')) {
            return false;
        }

        if ($request->routeIs('projects.filter', '*.preview', '*.test-connection', '*.stream', '*.zip')) {
            return false;
        }

        return true;
    }
}
