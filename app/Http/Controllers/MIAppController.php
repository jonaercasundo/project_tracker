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
use Illuminate\Support\Facades\DB;

class MIAppController extends Controller
{
    public function index(Request $request)
    {
        $query = MI_Product::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('item_name', 'like', '%' . $search . '%')
                  ->orWhere('category', 'like', '%' . $search . '%')
                  ->orWhere('collection', 'like', '%' . $search . '%');
            });
        }

        // Filter by classification (Available, Assigned, etc.)
        if ($request->filled('classification')) {
            $query->where('classification', $request->classification);
        }
        
        $products = $query->get();
        return view('mi_app.designer_module.index', compact('products'));
    }

    public function create() 
    {
        return view('mi_app.designer_module.create');
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
                    'code'        => strtoupper(substr($request->category_name, 0, 3)),
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
                    'code'        => strtoupper(substr($request->sub_category_name, 0, 3)),
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
                    'code'            => strtoupper(substr($request->collection_name, 0, 3)),
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
    public function store(Request $request) 
    {
        $validated = $request->validate([
            // General Info
            'item_name'      => 'required|string|max:255',
            'category'       => 'required|string|max:255',
            'collection'     => 'nullable|string|max:255',
            'type_of_sample' => 'required|string|max:255',
            'classification' => 'required|string|max:255',
            'designed_by'    => 'nullable|string|max:255',
            
            // Attributes & Dimensions
            'materials'      => 'required|string|max:255',
            'type'           => 'nullable|string|max:255',
            'color'          => 'nullable|string|max:255',
            'product_height' => 'required|string|max:255',
            'product_width'  => 'nullable|string|max:255',
            'product_length' => 'nullable|string|max:255',
            'product_depth'  => 'nullable|string|max:255',
            
            // Packaging & Cost
            'carton_height'  => 'nullable|string|max:255',
            'carton_width'   => 'nullable|string|max:255',
            'carton_length'  => 'nullable|string|max:255',
            'carton_depth'   => 'nullable|string|max:255',
            'purchase_cost'  => 'nullable|numeric',
            
            // File Upload (Images or 3D files like .obj, .stl) - Max size 20MB
            'product_file'   => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf,obj,stl|max:20480', 
        ]);
        
        // Handle the file upload if a file was attached in the form
        if ($request->hasFile('product_file')) {
            $file = $request->file('product_file');
            // Stores in storage/app/public/product_files
            $path = $file->store('product_files', 'public'); 
            $validated['product_file'] = $path;
        }

        MI_Product::create($validated);

        // NOTE: Make sure to update 'it.asset.index' to your new route name in web.php
        return redirect()->route('mi_app.index')->with('success', 'Product saved successfully!');
    }

    public function edit($id) 
    {
        $product = MI_Product::findOrFail($id); 
        return view('mi_app.designer_module.edit', compact('product')); 
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

    public function show($id) 
    {
        $product = MI_Product::findOrFail($id);
        return view('mi_app.designer_module.show', compact('product'));    
    }
}