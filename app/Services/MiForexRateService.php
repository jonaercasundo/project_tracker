<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MiForexRateService
{
    private const CACHE_KEY = 'mi.forex.usd_vnd';

    /** @return array{rate: string|null, source: string, updated_at: string|null} */
    public function currentRate(): array
    {
        $fresh = Cache::get(self::CACHE_KEY.'.fresh');
        if (is_array($fresh) && $this->isValidRate($fresh['rate'] ?? null)) {
            return $fresh;
        }

        try {
            $response = Http::connectTimeout(2)->timeout(4)->retry(2, 100)
                ->acceptJson()->get('https://open.er-api.com/v6/latest/USD');
            $rate = $response->successful() ? $response->json('rates.VND') : null;
            if ($response->successful() && $response->json('result') === 'success'
                && $response->json('base_code') === 'USD' && $this->isValidRate($rate)) {
                $timestamp = $response->json('time_last_update_unix');
                $updatedAt = is_numeric($timestamp)
                    ? Carbon::createFromTimestampUTC((int) $timestamp)->toIso8601String()
                    : now()->toIso8601String();
                $resolved = ['rate' => number_format((float) $rate, 4, '.', ''), 'source' => 'live', 'updated_at' => $updatedAt];
                Cache::put(self::CACHE_KEY.'.fresh', $resolved, now()->addDay());
                Cache::put(self::CACHE_KEY.'.last_known', $resolved, now()->addDays(7));

                return $resolved;
            }
        } catch (Throwable $exception) {
            Log::warning('USD/VND rate lookup failed.', ['exception' => $exception::class]);
        }

        $cached = Cache::get(self::CACHE_KEY.'.last_known');
        if (is_array($cached) && $this->isValidRate($cached['rate'] ?? null)) {
            $cached['source'] = 'cached';

            return $cached;
        }

        return ['rate' => null, 'source' => 'pending', 'updated_at' => null];
    }

    public function isValidRate(mixed $rate): bool
    {
        return is_numeric($rate) && (float) $rate >= 1000 && (float) $rate <= 100000
            && preg_match('/^\d+(\.\d{1,8})?$/D', (string) $rate) === 1;
    }
}
