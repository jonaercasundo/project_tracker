<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ITinventoryController extends Controller
{
    public function index(Request $request) //table
    {
        $query = Asset::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('asset_code', 'like', '%' . $search . '%')
                  ->orWhere('asset_name', 'like', '%' . $search . '%')
                  ->orWhere('category', 'like', '%' . $search . '%');
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $assets = $query->get();
        return view('it.asset_accountability_form.index', compact('assets'));
    }

    public function create() //create new asset method
    {
        return view('it.asset_accountability_form.create');
    }

    public function store(Request $request) //store asset method
    {
        $validated = $request->validate([
            'asset_code'      => 'required|string|max:255|unique:it_assets,asset_code',
            'asset_name'      => 'required|string|max:255',
            'category'        => 'required|string|max:255',
            'status'          => 'required|in:Available,Assigned,Repair,Disposed',
            'brand'           => 'nullable|string',
            'model'           => 'nullable|string',
            'serial_number'   => 'nullable|string',
            'specification'   => 'nullable|string',
            'purchase_date'   => 'nullable|date',
            'purchase_cost'   => 'nullable|numeric',
            'warranty_expiry' => 'nullable|date',
            'assigned_to'     => 'nullable|string',
            'department'      => 'nullable|string',
            'location'        => 'nullable|string',
            'remarks'         => 'nullable|string',
        ]);
        
        $asset = Asset::create($validated);

        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new \BaconQrCode\Writer($renderer);
        $qrCodeSvg = $writer->writeString(route('it.asset.show', $asset->id));

        $filename = 'qrcodes/' . $asset->asset_code . '.svg';
        Storage::disk('public')->put($filename, $qrCodeSvg);

        $asset->update(['qr_code' => $filename]);

        return redirect()->route('it.asset.index')->with('success', 'IT Asset saved successfully!');
    }

    public function edit($id) //edit asset method
    {
        $asset = Asset::findOrFail($id); 
        return view('it.asset_accountability_form.edit', compact('asset')); 
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'asset_code'      => 'required|string|max:255|unique:it_assets,asset_code,' . $asset->id,
            'asset_name'      => 'required|string|max:255',
            'category'        => 'required|string|max:255',
            'status'          => 'required|in:Available,Assigned,Repair,Disposed',
            'brand'           => 'nullable|string',
            'model'           => 'nullable|string',
            'serial_number'   => 'nullable|string',
            'specification'   => 'nullable|string',
            'purchase_date'   => 'nullable|date',
            'purchase_cost'   => 'nullable|numeric',
            'warranty_expiry' => 'nullable|date',
            'assigned_to'     => 'nullable|string',
            'department'      => 'nullable|string',
            'location'        => 'nullable|string',
            'remarks'         => 'nullable|string',
        ]);

        $asset->update($validated);
        return redirect()->route('it.asset.index')->with('success', 'IT Asset updated successfully!');
    }
    public function destroy(\App\Models\Asset $asset) //delete
    {
        $asset->delete();
        return redirect()->route('it.asset.index')->with('success', 'Asset deleted successfully.');
    }

    public function show($id) // show qr code
    {
        $asset = Asset::findOrFail($id);
        return view('it.asset_accountability_form.show', compact('asset'));    
    }

}