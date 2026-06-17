<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncTikTokTrends extends Command
{
    protected $signature   = 'tiktok:sync {--hashtags=homedecor,interiordesign,roomdecor}';
    protected $description = 'Fetch latest TikTok trends and store in database';

    public function handle()
    {
        $apiToken = config('services.apify.token');
        $hashtags = explode(',', $this->option('hashtags'));

        $this->info('Starting TikTok sync for: ' . implode(', ', $hashtags));

        // Step 1 — trigger run
        $runResponse = Http::timeout(60)
            ->withToken($apiToken, 'Bearer')
            ->post("https://api.apify.com/v2/acts/clockworks~tiktok-scraper/runs", [
                "hashtags"          => $hashtags,
                "resultsPerPage"    => 10,
                "maxRequestRetries" => 3,
            ]);

        if ($runResponse->failed()) {
            $this->error('Failed to trigger Apify run');
            Log::error('TikTok sync failed', ['body' => $runResponse->body()]);
            return 1;
        }

        $runId = data_get($runResponse->json(), 'data.id');
        $this->info("Run started: {$runId}");

        // Step 2 — poll until done
        $status   = 'RUNNING';
        $attempts = 0;

        while (in_array($status, ['RUNNING', 'READY'])) {
            sleep(3);
            $attempts++;
            $this->line("Polling... attempt {$attempts} ({$status})");

            $statusRes = Http::withToken($apiToken, 'Bearer')
                ->get("https://api.apify.com/v2/actor-runs/{$runId}");

            $status = data_get($statusRes->json(), 'data.status', 'FAILED');

            if ($attempts >= 20) break;
        }

        if ($status !== 'SUCCEEDED') {
            $this->error("Run did not succeed. Status: {$status}");
            return 1;
        }

        // Step 3 — fetch dataset
        $datasetRes = Http::withToken($apiToken, 'Bearer')
            ->get("https://api.apify.com/v2/actor-runs/{$runId}/dataset/items", [
                'format' => 'json',
                'clean'  => true,
            ]);

        $items = $datasetRes->json() ?? [];
        $saved = 0;

        foreach ($items as $item) {
            $tiktokId = data_get($item, 'id');
            if (!$tiktokId) continue;

            DB::table('tiktok_trends')->updateOrInsert(
                ['tiktok_id' => $tiktokId],
                [
                    'hashtag'         => data_get($item, 'searchHashtag.name', $hashtags[0]),
                    'description'     => data_get($item, 'text'),
                    'author_name'     => data_get($item, 'authorMeta.name'),
                    'author_nickname' => data_get($item, 'authorMeta.nickName'),
                    'author_id'       => data_get($item, 'authorMeta.name'),
                    'author_url'      => data_get($item, 'authorMeta.profileUrl'),
                    'avatar_url'      => data_get($item, 'authorMeta.avatar'),
                    'fans'            => data_get($item, 'authorMeta.fans', 0),
                    'cover_url'       => data_get($item, 'videoMeta.coverUrl'),
                    'post_url'        => data_get($item, 'webVideoUrl'),
                    'likes'           => data_get($item, 'diggCount', 0),
                    'views'           => data_get($item, 'playCount', 0),
                    'shares'          => data_get($item, 'shareCount', 0),
                    'comments'        => data_get($item, 'commentCount', 0),
                    'duration'        => data_get($item, 'videoMeta.duration', 0),
                    'region'          => data_get($item, 'authorMeta.region'),
                    'hashtags'        => json_encode(collect(data_get($item, 'hashtags', []))->pluck('name')->filter()->values()),
                    'posted_at'       => data_get($item, 'createTimeISO') ? Carbon::parse(data_get($item, 'createTimeISO')) : null,
                    'updated_at'      => now(),
                    'created_at'      => now(),
                ]
            );
            $saved++;
        }

        $this->info("✅ Sync complete. {$saved} trends saved.");
        return 0;
    }
}
