<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MI_Product; // Ensure the model is imported
use Illuminate\Support\Facades\Storage;
use App\Models\MI_Category;
use App\Models\MI_SubCategory;
use App\Models\MI_ProductType;

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
        $categories = MI_Category::orderBy('category_name')->get();
        $subCategories = MI_SubCategory::orderBy('sub_category_name')->get();
        $subSubCategories = MI_ProductType::orderBy('sub_sub_category_name')->get();

        return view('mi_app.designer_module.settings', compact(
            'categories',
            'subCategories',
            'subSubCategories'
        ));
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