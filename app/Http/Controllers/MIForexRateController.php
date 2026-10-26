<?php

namespace App\Http\Controllers;

use App\Services\MiForexRateService;
use Illuminate\Http\JsonResponse;

class MIForexRateController extends Controller
{
    public function __invoke(MiForexRateService $rates): JsonResponse
    {
        $rate = $rates->currentRate();

        return response()->json([
            'available' => $rate['rate'] !== null,
            ...$rate,
            'message' => $rate['rate'] === null ? 'Conversion is pending. The report can still be saved.' : null,
        ]);
    }
}
