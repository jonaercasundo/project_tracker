<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function regions()
    {
        return DB::table('region')
            ->select('region_id', 'region_name')
            ->get();
    }
    public function divisions(Request $request)
    {
        return DB::table('division')
            ->where('region_id', $request->region)
            ->select('division_id', 'division_name')
            ->get();
    }
    public function municipalities(Request $request)
    {
        return DB::table('municipality')
            ->where('division_id', $request->division)
            ->select('municipality_id', 'municipality_name')
            ->get();
    }
}
