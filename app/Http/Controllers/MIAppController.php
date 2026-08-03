<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MI_Product; // Ensure the model is imported
use Illuminate\Support\Facades\Storage;
use App\Models\MI_Category;
use App\Models\MI_SubCategory;
use App\Models\MI_ProductType;
use App\Models\MI_Collection;
use App\Models\MI_Material;
use App\Models\MI_Product_Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\log;

class MIAppController extends Controller
{
public function index(Request $request)
{
    $query = MI_Product::with([
        'category',
        'subCategory',
        'productType',
        'collection',
    ]);

    // Search
    if ($request->filled('search')) {
        $search = trim($request->search);

        $query->where(function ($q) use ($search) {

            // Product fields
            $q->where('item_name', 'like', "%{$search}%")
              ->orWhere('type_of_sample', 'like', "%{$search}%")
              ->orWhere('designed_by', 'like', "%{$search}%")
              ->orWhere('classification', 'like', "%{$search}%");

            // Category
            $q->orWhereHas('category', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
            });

            // Sub Category
            $q->orWhereHas('subCategory', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
            });

            // Product Type
            $q->orWhereHas('productType', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
            });

            // Collection
            $q->orWhereHas('collection', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
            });
        });
    }

    // Filter by Classification
    if ($request->filled('classification')) {
        $query->where('classification', $request->classification);
    }

    // Filter by Status (optional)
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    $products = $query->latest()->paginate(15);

    return view('mi_app.designer_module.index', compact('products'));
}
public function dashboard()
{
    $stats = [
        'total_products'    => MI_Product::count(),
        'active_products'   => MI_Product::where('classification', 'Available')->count(),
        'total_categories'  => MI_Category::count(),
        'total_collections' => MI_Collection::count(),
    ];

    $taxonomyCounts = [
        'categories'     => MI_Category::count(),
        'sub_categories' => MI_SubCategory::count(),
        'product_types'  => MI_ProductType::count(),
        'collections'    => MI_Collection::count(),
    ];

    $classificationBreakdown = MI_Product::selectRaw('classification, count(*) as count')
        ->whereNotNull('classification')
        ->groupBy('classification')
        ->pluck('count', 'classification');

    $categoryBreakdown = MI_Product::with('category')
        ->get()
        ->groupBy(fn ($product) => $product->category->name ?? 'Uncategorized')
        ->map(fn ($group, $name) => [
            'name'  => $name,
            'count' => $group->count(),
        ])
        ->values()
        ->sortByDesc('count')
        ->take(8)
        ->values();

    $recentProducts = MI_Product::with('category')
        ->latest()
        ->take(8)
        ->get();

    return view('mi_app.designer_module.dashboard', compact(
        'stats',
        'taxonomyCounts',
        'classificationBreakdown',
        'categoryBreakdown',
        'recentProducts'
    ));
}
    public function create()
    {
        $categories    = MI_Category::orderBy('name')->get();
        $subCategories = MI_SubCategory::orderBy('name')->get();
        $productTypes  = MI_ProductType::orderBy('name')->get();
        $collections   = MI_Collection::orderBy('name')->get();

        return view('mi_app.designer_module.create', compact(
            'categories',
            'subCategories',
            'productTypes',
            'collections'
        ));
    }
    public function settings()
    {
        $categories = MI_Category::orderBy('name')->get();

        $subCategories = MI_SubCategory::orderBy('name')->get();

        $productTypes = MI_ProductType::orderBy('name')->get();

        $collections = MI_Collection::orderBy('name')->get();

        $materials = MI_Material::orderBy('material_name')->get();

        return view('mi_app.designer_module.settings', compact(
            'categories',
            'subCategories',
            'productTypes',
            'collections',
            'materials'
        ));
    }
    public function setting_store(Request $request)
    {
        DB::beginTransaction();

        try {

            switch ($request->entity_type) {

                /*
                |--------------------------------------------------------------------------
                | Category
                |--------------------------------------------------------------------------
                */
                case 'category':

                    $request->validate([
                        'category_name' => 'required|string|max:255|unique:mi_categories,name',
                    ]);

                    MI_Category::create([
                        'code'        => $this->generateUniqueCode(MI_Category::class, $request->category_name),
                        'name'        => $request->category_name,
                        'description' => $request->description,
                        'is_active'   => true,
                    ]);

                    break;

                /*
                |--------------------------------------------------------------------------
                | Sub Category
                |--------------------------------------------------------------------------
                */
                case 'sub_category':

                    $request->validate([
                        'category_id'      => 'required|exists:mi_categories,id',
                        'sub_category_name'=> 'required|string|max:255',
                    ]);

                    MI_SubCategory::create([
                        'category_id' => $request->category_id,
                        'code'        => $this->generateUniqueCode(MI_SubCategory::class, $request->sub_category_name),
                        'name'        => $request->sub_category_name,
                        'description' => $request->description,
                        'is_active'   => true,
                    ]);

                    break;

                /*
                |--------------------------------------------------------------------------
                | Product Type
                |--------------------------------------------------------------------------
                */
                case 'product_type':

                    $request->validate([
                        'sub_category_id' => 'required|exists:mi_sub_categories,id',
                        'product_type_name' => 'required|string|max:255',
                    ]);

                    MI_ProductType::create([
                        'sub_category_id' => $request->sub_category_id,
                        'code'            => strtoupper(substr($request->product_type_name, 0, 3)),
                        'name'            => $request->product_type_name,
                        'description'     => $request->description,
                        'is_active'       => true,
                    ]);

                    break;

                /*
                |--------------------------------------------------------------------------
                | Collection
                |--------------------------------------------------------------------------
                */
                case 'collection':

                    $request->validate([
                        'product_type_id' => 'required|exists:mi_product_types,id',
                        'collection_name' => 'required|string|max:255',
                    ]);

                    MI_Collection::create([
                        'product_type_id' => $request->product_type_id,
                        'code'        => $this->generateUniqueCode(MI_Collection::class, $request->collection_name),
                        'name'            => $request->collection_name,
                        'description'     => $request->description,
                        'is_active'       => true,
                    ]);

                    break;

                /*
                |--------------------------------------------------------------------------
                | Material
                |--------------------------------------------------------------------------
                */
                case 'material':

                    $request->validate([
                        'material_name' => 'required|string|max:255|unique:mi_materials,material_name',
                    ]);

                    MI_Material::create([
                        'material_name' => $request->material_name,
                        'is_active'     => true,
                    ]);

                    break;

                default:
                    return back()->withErrors([
                        'entity_type' => 'Invalid request.'
                    ]);
            }

            DB::commit();

            return back()->with('success', 'Record saved successfully.');

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors([
                    'error' => $e->getMessage()
                ]);
        }
    }
    private function generateUniqueCode(string $model, string $name): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z]/', '', $name));
        $base = str_pad(substr($base, 0, 3), 3, 'X'); // e.g. "IND"

        if (! $model::where('code', $base)->exists()) {
            return $base;
        }

        // Try skipping letters within the name (e.g. "INR" from "INdooR")
        for ($i = 1; $i <= strlen($base) - 1 && strlen($base) >= 3; $i++) {
            // fallback below handles the common case reliably
        }

        // Reliable fallback: keep first 2 letters, append a running number
        $prefix = substr($base, 0, 2);
        $n = 1;
        do {
            $candidate = $prefix . $n;
            $n++;
        } while ($model::where('code', $candidate)->exists());

        return $candidate;
    }
    public function store(Request $request)
    {
        $validated = $request->validate([

            // Product Information
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string',

            // Taxonomy
            'category_id' => 'required|integer|exists:mi_categories,id',
            'sub_category_id' => 'nullable|integer|exists:mi_sub_categories,id',
            'product_type_id' => 'nullable|integer|exists:mi_product_types,id',
            'collection_id' => 'nullable|integer|exists:mi_collections,id',

            // Product Details
            'type_of_sample' => 'required|string|max:255',
            'designed_by' => 'nullable|string|max:255',

            // Attributes
            'materials' => 'required|array|min:1',
            'materials.*' => 'string|max:255',

            'type' => 'nullable|string|max:255',

            'color' => 'nullable|array',
            'color.*' => 'string|max:255',

            // Product Dimensions
            'product_height' => 'nullable|numeric',
            'product_width' => 'nullable|numeric',
            'product_length' => 'nullable|numeric',
            'product_depth' => 'nullable|numeric',

            // Carton Dimensions
            'carton_height' => 'nullable|numeric',
            'carton_width' => 'nullable|numeric',
            'carton_length' => 'nullable|numeric',
            'carton_depth' => 'nullable|numeric',

            // Cost
            'purchase_cost' => 'nullable|numeric',

            // File
            'product_file' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf,obj,stl|max:20480',
        ]);

        // Save arrays as JSON
        $validated['materials'] = !empty($validated['materials'])
            ? json_encode($validated['materials'])
            : null;

        $validated['color'] = !empty($validated['color'])
            ? json_encode($validated['color'])
            : null;

        $validated['status'] = 'Active';

        DB::beginTransaction();

        try {

            // Upload file only inside the transaction attempt, so a DB failure
            // doesn't leave an orphaned file sitting in storage.
            if ($request->hasFile('product_file')) {
                $validated['product_file'] = $request
                    ->file('product_file')
                    ->store('product_files', 'public');
            }

        $product = MI_Product::create($validated);
        if($request->image_links){

            foreach($request->image_links as $index=>$url){

                if(!empty($url)){

                    MI_Product_Image::create([
                        'product_id'=>$product->product_id,
                        'image_type'=>'url',
                        'image_url'=>$url,
                        'is_primary'=>$index == 0,
                        'sort_order'=>$index
                    ]);

                }

            }

        }
        if($request->hasFile('product_images')){
            foreach($request->file('product_images') as $index=>$file){


                $path = $file->store(
                    'product_images',
                    'public'
                );


                MI_Product_Image::create([

                    'product_id'=>$product->product_id,

                    'image_type'=>'upload',

                    'image_path'=>$path,

                    'is_primary'=>
                        empty($request->image_links) && $index == 0,

                    'sort_order'=>$index

                ]);


            }

        }
        /*
        |--------------------------------------------------------------------------
        | Convert Google Drive Image Link
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['image_link'])) {

            if (preg_match('/\/d\/([^\/]+)/', $validated['image_link'], $matches)) {

                $fileId = $matches[1];

                $product->image_link =
                    "https://drive.google.com/uc?export=view&id=".$fileId;
            }
        }
        /*
        |--------------------------------------------------------------------------
        | Auto Generate Draft Number
        |--------------------------------------------------------------------------
        */

        $product->draft_number = 'DR-' 
            . date('Y') 
            . '-' 
            . str_pad($product->product_id, 4, '0', STR_PAD_LEFT);


        /*
        |--------------------------------------------------------------------------
        | Auto Generate SKU
        |--------------------------------------------------------------------------
        | Example:
        | HD-IN-AAL-0001
        |
        | Category Code
        | Sub Category Code
        | Collection Code
        | Sequence
        |--------------------------------------------------------------------------
        */

        $category = MI_Category::find($product->category_id);

        $subCategory = MI_SubCategory::find($product->sub_category_id);

        $subsubCategory = MI_ProductType::find($product->product_type_id);

        $collection = MI_Collection::find($product->collection_id);


        $categoryCode = strtoupper(
            substr($category->code ?? 'GEN', 0, 2)
        );


        $subCategoryCode = strtoupper(
            substr($subCategory->code ?? 'XX', 0, 2)
        );

        $subsubCategory = strtoupper(
            substr($subsubCategory->code ?? 'XX', 0, 2)
        );

        $collectionCode = strtoupper(
            substr($collection->code ?? 'XXX', 0, 3)
        );


        $product->sku =
            $categoryCode
            . '-'
            . $subCategoryCode
            . '-'
            . $collectionCode
            . '-'
            . $subsubCategory
            . '-'
            . str_pad($product->product_id, 4, '0', STR_PAD_LEFT);


        /*
        |--------------------------------------------------------------------------
        | Save Generated Values
        |--------------------------------------------------------------------------
        */

        $product->save();


        DB::commit();

                    return redirect()
                        ->route('mi_app.index')
                        ->with('success', 'Product saved successfully!');

                } catch (\Throwable $e) {

                    DB::rollBack();

                    // Clean up the uploaded file if the DB write failed after upload
                    if (!empty($validated['product_file'])) {
                        Storage::disk('public')->delete($validated['product_file']);
                    }

                    Log::error('Product save failed: ' . $e->getMessage(), [
                        'exception' => $e,
                        'input' => $request->except('product_file'),
                    ]);

                    return back()
                        ->withInput()
                        ->withErrors([
                            'error' => app()->environment('production')
                                ? 'Something went wrong while saving the product. Please try again or contact support.'
                                : $e->getMessage(),
                        ]);
                }
    }

    public function edit($id)
    {
        $product = MI_Product::with([
            'category',
            'subCategory',
            'productType',
            'collection'
        ])->findOrFail($id);


        $categories = MI_Category::orderBy('name')->get();

        $subCategories = MI_SubCategory::orderBy('name')->get();

        $productTypes = MI_ProductType::orderBy('name')->get();

        $collections = MI_Collection::orderBy('name')->get();


        return view('mi_app.designer_module.edit', compact(
            'product',
            'categories',
            'subCategories',
            'productTypes',
            'collections'
        ));
    }

    public function update(Request $request, $id)
    {
        $product = MI_Product::findOrFail($id);

        $validated = $request->validate([
            'item_name'      => 'required|string|max:255',
            'category'       => 'required|string|max:255',
            'collection'     => 'nullable|string|max:255',
            'type_of_sample' => 'required|string|max:255',
            'classification' => 'required|string|max:255',
            'designed_by'    => 'nullable|string|max:255',
            'materials'      => 'required|string|max:255',
            'type'           => 'nullable|string|max:255',
            'color'          => 'nullable|string|max:255',
            'product_height' => 'required|string|max:255',
            'product_width'  => 'nullable|string|max:255',
            'product_length' => 'nullable|string|max:255',
            'product_depth'  => 'nullable|string|max:255',
            'carton_height'  => 'nullable|string|max:255',
            'carton_width'   => 'nullable|string|max:255',
            'carton_length'  => 'nullable|string|max:255',
            'carton_depth'   => 'nullable|string|max:255',
            'purchase_cost'  => 'nullable|numeric',
            'product_file'   => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf,obj,stl|max:20480', 
        ]);

        if ($request->hasFile('product_file')) {
            // Optional: Delete the old file before saving the new one
            if ($product->product_file) {
                Storage::disk('public')->delete($product->product_file);
            }
            
            $path = $request->file('product_file')->store('product_files', 'public');
            $validated['product_file'] = $path;
        }

        $product->update($validated);
        
        return redirect()->route('mi_app.index')->with('success', 'Product updated successfully!');
    }

    public function destroy($id) 
    {
        $product = MI_Product::findOrFail($id);
        
        // Delete the associated file from storage to free up space
        if ($product->product_file) {
            Storage::disk('public')->delete($product->product_file);
        }

        $product->delete();
        
        return redirect()->route('mi_app.index')->with('success', 'Product deleted successfully.');
    }

    public function show(MI_Product $product)
    {
        $product->load([
            'category',
            'subCategory',
            'productType',
            'collection',
        ]);

        return view('mi_app.designer_module.show', compact('product'));
    }

public function taxonomy_edit($type, $id)
{
    switch ($type) {
        case 'category':
            $item = MI_Category::findOrFail($id);
            break;
        case 'sub_category':
            $item = MI_SubCategory::findOrFail($id);
            break;
        case 'product_type':
            $item = MI_ProductType::findOrFail($id);
            break;
        case 'collection':
            $item = MI_Collection::findOrFail($id);
            break;
        default:
            abort(404, 'Invalid taxonomy type.');
    }

    return view('mi_app.designer_module.taxonomy_edit', compact('item', 'type'));
}

public function taxonomy_update(Request $request, $type, $id)
{
    switch ($type) {
        case 'category':
            $item = MI_Category::findOrFail($id);
            $request->validate([
                'name' => 'required|string|max:255|unique:mi_categories,name,' . $item->id,
            ]);
            break;
        case 'sub_category':
            $item = MI_SubCategory::findOrFail($id);
            $request->validate([
                'name' => 'required|string|max:255|unique:mi_sub_categories,name,' . $item->id,
            ]);
            break;
        case 'product_type':
            $item = MI_ProductType::findOrFail($id);
            $request->validate([
                'name' => 'required|string|max:255|unique:mi_product_types,name,' . $item->id,
            ]);
            break;
        case 'collection':
            $item = MI_Collection::findOrFail($id);
            $request->validate([
                'name' => 'required|string|max:255|unique:mi_collections,name,' . $item->id,
            ]);
            break;
        default:
            abort(404, 'Invalid taxonomy type.');
    }

    $item->update($request->only('name', 'description'));

    return redirect()
        ->route('mi_app.settings')
        ->with('success', ucfirst(str_replace('_', ' ', $type)) . ' updated successfully.');
}
public function taxonomy_destroy($type, $product)
{
    switch ($type) {

        case 'category':
            $item = MI_Category::findOrFail($product);
            break;

        case 'sub_category':
            $item = MI_SubCategory::findOrFail($product);
            break;

        case 'product_type':
            $item = MI_ProductType::findOrFail($product);
            break;

        case 'collection':
            $item = MI_Collection::findOrFail($product);
            break;

        default:
            abort(404);
    }

    dd($item);

    $item->delete();

    return redirect()->route('mi_app.settings')
        ->with('success', 'Deleted successfully.');
}
}