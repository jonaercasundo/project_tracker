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

        // Step 1 — trigger live run
        $runResponse = Http::timeout(120)
            ->withToken($apiToken, 'Bearer')
            ->post("https://api.apify.com/v2/acts/clockworks~tiktok-scraper/runs", [
                "hashtags"          => $hashtags,
                "resultsPerPage"    => 100,
                "maxRequestRetries" => 3,
                "feedType"          => "trending",
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

        // Step 2 — poll until finished (max 3 mins)
        $status   = 'RUNNING';
        $attempts = 0;
        $maxAttempts = 60; // 60 x 3s = 3 minutes

        while (in_array($status, ['RUNNING', 'READY'])) {
            sleep(3);
            $attempts++;

            $statusRes = Http::withToken($apiToken, 'Bearer')
                ->get("https://api.apify.com/v2/actor-runs/{$runId}");

            $status = data_get($statusRes->json(), 'data.status', 'FAILED');

            if ($attempts >= $maxAttempts) break;
        }

        if ($status !== 'SUCCEEDED') {
            Log::error('Apify run did not succeed', [
                'runId'    => $runId,
                'status'   => $status,
                'attempts' => $attempts,
            ]);
            return view('tiktok.index', [
                'items'    => [],
                'error'    => 'Scraper did not finish in time. Please try again.',
                'hashtags' => $hashtags,
                'country'  => $country,
            ]);
        }

        // Step 3 — fetch live results
        $datasetRes = Http::withToken($apiToken, 'Bearer')
            ->get("https://api.apify.com/v2/actor-runs/{$runId}/dataset/items", [
                'format' => 'json',
                'clean'  => true,
            ]);

        $items = $datasetRes->json() ?? [];

        // Keep only high engagement videos
        $items = array_values(array_filter($items, function ($item) {
            $likes = $item['diggCount'] ?? 0;
            $views = $item['playCount'] ?? 0;
            $fans  = data_get($item, 'authorMeta.fans', 0);

            return $likes >= 100000     // 100k+ likes
                || $views >= 1000000    // OR 1M+ views
                || $fans  >= 100000;    // OR 100k+ followers
        }));

        // Sort by most liked first
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

        // Fallback message if filters removed everything
        $error = null;
        if (empty($items)) {
            $error = 'No trending videos found for these hashtags. '
                   . 'Try a broader hashtag like #homedecor or #interiordesign.';
        }

        return view('tiktok.index', [
            'items'    => $items,
            'error'    => $error,
            'hashtags' => $hashtags,
            'country'  => $country,
        ]);
    }
}