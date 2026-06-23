<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    // REGIONS
    public function regions()
    {
        return DB::table('school')
            ->select('region')
            ->distinct()
            ->orderBy('region')
            ->get();
    }

    // DIVISIONS (depends on region)
    public function divisions(Request $request)
    {
        return DB::table('school')
            ->where('region', $request->region)
            ->select('division')
            ->distinct()
            ->orderBy('division')
            ->get();
    }

    // MUNICIPALITIES (depends on division)
    public function municipalities(Request $request)
    {
        return DB::table('school')
            ->where('division', $request->division)
            ->select('municipality')
            ->distinct()
            ->orderBy('municipality')
            ->get();
    }
}