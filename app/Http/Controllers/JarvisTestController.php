<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class JarvisTestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'JARVIS connected to MMC Tracker',
        ]);
    }
}
