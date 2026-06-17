<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class TikTokController extends Controller
{
    public function fetchHomeDecorTrends(Request $request)
    {
        $apiToken = config('services.apify.token');
        $hashtags = $request->input('hashtags', ['homedecor']);
        $hashtags = array_values(array_filter(array_map('trim', (array) $hashtags)));
        $country  = $request->input('country', '');

        if (empty($apiToken)) {
            return view('tiktok.index', [
                'items'    => [],
                'error'    => 'API token is not configured.',
                'hashtags' => $hashtags,
                'country'  => $country,
            ]);
        }

        // Step 1 — trigger live run with searched hashtags
        $runResponse = Http::timeout(60)
            ->withToken($apiToken, 'Bearer')
            ->post("https://api.apify.com/v2/acts/clockworks~tiktok-scraper/runs", [
                "hashtags"          => $hashtags,  // whatever user typed
                "resultsPerPage"    => 10,
                "maxRequestRetries" => 3,
            ]);

        if ($runResponse->failed()) {
            Log::error('Apify run trigger failed', [
                'status' => $runResponse->status(),
                'body'   => $runResponse->body(),
            ]);
            return view('tiktok.index', [
                'items'    => [],
                'error'    => 'Failed to start scraper. Please try again.',
                'hashtags' => $hashtags,
                'country'  => $country,
            ]);
        }

        $runId = data_get($runResponse->json(), 'data.id');

        if (!$runId) {
            return view('tiktok.index', [
                'items'    => [],
                'error'    => 'No run ID returned from Apify.',
                'hashtags' => $hashtags,
                'country'  => $country,
            ]);
        }

        // Step 2 — poll until finished
        $status   = 'RUNNING';
        $attempts = 0;

        while (in_array($status, ['RUNNING', 'READY'])) {
            sleep(3);
            $attempts++;

            $statusRes = Http::withToken($apiToken, 'Bearer')
                ->get("https://api.apify.com/v2/actor-runs/{$runId}");

            $status = data_get($statusRes->json(), 'data.status', 'FAILED');

            if ($attempts >= 20) break;
        }

        if ($status !== 'SUCCEEDED') {
            Log::error('Apify run did not succeed', [
                'runId'    => $runId,
                'status'   => $status,
                'attempts' => $attempts,
            ]);
            return view('tiktok.index', [
                'items'    => [],
                'error'    => "Scraper did not finish in time. Status: {$status}",
                'hashtags' => $hashtags,
                'country'  => $country,
            ]);
        }

        // Step 3 — fetch results
        $datasetRes = Http::withToken($apiToken, 'Bearer')
            ->get("https://api.apify.com/v2/actor-runs/{$runId}/dataset/items", [
                'format' => 'json',
                'clean'  => true,
            ]);

        $items = $datasetRes->json() ?? [];

        // Sort by most liked
        usort($items, fn($a, $b) =>
            ($b['diggCount'] ?? 0) - ($a['diggCount'] ?? 0)
        );

        // Filter by country if selected
        if (!empty($country)) {
            $items = array_values(array_filter($items, function ($item) use ($country) {
                $region = data_get($item, 'authorMeta.region')
                       ?? data_get($item, 'locationCreated')
                       ?? '';
                return strtoupper($region) === strtoupper($country);
            }));
        }

        return view('tiktok.index', [
            'items'    => $items,
            'error'    => null,
            'hashtags' => $hashtags,
            'country'  => $country,
            'runId'    => $runId,
        ]);
    }
}