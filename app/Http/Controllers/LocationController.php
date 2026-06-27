<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Psgc;

class LocationController extends Controller
{
    // REGIONS
    public function region()
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
            ->get();
    }

    // MUNICIPALITIES (depends on division)
    public function municipalities(Request $request)
    {
        return DB::table('school')
            ->where('division', $request->division)
            ->select('municipality')
            ->distinct()
            ->get();
    }

    public function countries()
    {
        return response()->json([
            ['code'=>'PH','name'=>'Philippines']
        ]);
    }
    public function regions()
    {
        return Psgc::where('geographic_level','Reg')
            ->orderBy('name')
            ->get([
                'psgc_code as code',
                'name'
            ]);
    }

    public function provinces(Request $request)
    {
        return Psgc::where('geographic_level','Prov')
            ->where('region_code',$request->region)
            ->orderBy('name')
            ->get([
                'psgc_code as code',
                'name'
            ]);
    }

    public function cities(Request $request)
    {
        $query = Psgc::whereIn('geographic_level', ['City', 'Mun', 'SubMun']);

        if ($request->filled('province')) {
            $query->where('province_code', $request->province);
        } elseif ($request->filled('region')) {
            $query->where('region_code', $request->region);
        }

        return $query->orderBy('name')->get([
            'psgc_code as code',
            'name'
        ]);
    }

    public function barangays(Request $request)
    {
        return Psgc::where('geographic_level','Bgy')
            ->where('city_code',$request->city)
            ->orderBy('name')
            ->get([
                'psgc_code as code',
                'name'
            ]);
    }
}