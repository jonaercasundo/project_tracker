<?php

namespace App\Http\Controllers;

use App\Models\MI_Category;
use App\Models\MI_Collection;
use App\Models\MI_Material;
use App\Models\MI_Product;
use App\Models\MI_Product_Image;
use App\Models\MI_ProductType;
use App\Models\MI_SubCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            'total_products' => MI_Product::count(),
            'active_products' => MI_Product::where('classification', 'Available')->count(),
            'total_categories' => MI_Category::count(),
            'total_collections' => MI_Collection::count(),
        ];

        $taxonomyCounts = [
            'categories' => MI_Category::count(),
            'sub_categories' => MI_SubCategory::count(),
            'product_types' => MI_ProductType::count(),
            'collections' => MI_Collection::count(),
        ];

        $classificationBreakdown = MI_Product::selectRaw('classification, count(*) as count')
            ->whereNotNull('classification')
            ->groupBy('classification')
            ->pluck('count', 'classification');

        $categoryBreakdown = MI_Product::with('category')
            ->get()
            ->groupBy(fn ($product) => $product->category->name ?? 'Uncategorized')
            ->map(fn ($group, $name) => [
                'name' => $name,
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
        $categories = MI_Category::orderBy('name')->get();
        $subCategories = MI_SubCategory::orderBy('name')->get();
        $productTypes = MI_ProductType::orderBy('name')->get();
        $collections = MI_Collection::orderBy('name')->get();

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
                        'code' => $this->generateUniqueCode(MI_Category::class, $request->category_name),
                        'name' => $request->category_name,
                        'description' => $request->description,
                        'is_active' => true,
                    ]);

                    break;

                    /*
                    |--------------------------------------------------------------------------
                    | Sub Category
                    |--------------------------------------------------------------------------
                    */
                case 'sub_category':

                    $request->validate([
                        'category_id' => 'required|exists:mi_categories,id',
                        'sub_category_name' => 'required|string|max:255',
                    ]);

                    MI_SubCategory::create([
                        'category_id' => $request->category_id,
                        'code' => $this->generateUniqueCode(MI_SubCategory::class, $request->sub_category_name),
                        'name' => $request->sub_category_name,
                        'description' => $request->description,
                        'is_active' => true,
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
                        'code' => $this->generateUniqueCode(
                            MI_ProductType::class,
                            $request->product_type_name
                        ),
                        'name' => $request->product_type_name,
                        'description' => $request->description,
                        'is_active' => true,
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
                        'code' => $this->generateUniqueCode(MI_Collection::class, $request->collection_name),
                        'name' => $request->collection_name,
                        'description' => $request->description,
                        'is_active' => true,
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
                        'is_active' => true,
                    ]);

                    break;

                default:
                    return back()->withErrors([
                        'entity_type' => 'Invalid request.',
                    ]);
            }

            DB::commit();

            return back()->with('success', 'Record saved successfully.');

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors([
                    'error' => $e->getMessage(),
                ]);
        }
    }

    private function normalizeArrayInput($value): array
    {
        if (! is_array($value)) {
            $value = $value ? [$value] : [];
        }

        return array_values(array_filter(array_map(function ($item) {
            return trim((string) $item);
        }, $value), function ($item) {
            return $item !== '';
        }));
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
            $candidate = $prefix.$n;
            $n++;
        } while ($model::where('code', $candidate)->exists());

        return $candidate;
    }

    /**
     * Store an uploaded file safely.
     *
     * Validates the file arrived intact and that the storage write
     * actually succeeded, throwing a clear exception otherwise instead
     * of silently saving a broken path (e.g. `false` cast to `0`).
     *
     * @throws \RuntimeException
     */
    private function storeUploadedFile(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        if (! $file->isValid()) {
            throw new \RuntimeException(
                'Upload failed for "'.$file->getClientOriginalName()
                .'" (error code: '.$file->getError().'). Please try uploading the file again.'
            );
        }

        $path = $file->store($directory, $disk);

        if ($path === false || $path === null || $path === '') {
            throw new \RuntimeException(
                'Failed to save uploaded file "'.$file->getClientOriginalName().'" to storage. Please try again.'
            );
        }

        return $path;
    }

    public function store(Request $request)
    {
        // Remove blank image link values before validation so optional empty inputs do not fail the URL rule.
        if ($request->has('image_links')) {
            $request->merge([
                'image_links' => array_values(array_filter($request->input('image_links', []), function ($value) {
                    return trim((string) $value) !== '';
                })),
            ]);
        }

        $validated = $request->validate([

            // Product Information
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string',

            // Taxonomy
            'category_id' => 'required|integer|exists:mi_categories,id',
            'sub_category_id' => 'required|integer|exists:mi_sub_categories,id',
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

            // Media
            'product_images' => 'nullable|array',
            'product_images.*' => 'file|mimes:jpeg,png,jpg,webp,pdf,obj,stl|max:20480',
            'image_links' => 'nullable|array',
            'image_links.*' => 'nullable|url|max:1000',
        ]);

        $this->validateProductTaxonomy($validated);
        $uploadedPaths = [];

        // Save arrays as JSON
        $validated['materials'] = $this->normalizeArrayInput($validated['materials'] ?? []);
        $validated['color'] = $this->normalizeArrayInput($validated['color'] ?? []);

        $validated['status'] = 'Active';

        DB::beginTransaction();

        try {

            $product = MI_Product::create($validated);

            if ($request->image_links) {
                foreach ($request->image_links as $index => $url) {
                    if (! empty($url)) {
                        MI_Product_Image::create([
                            'product_id' => $product->product_id,
                            'image_type' => 'url',
                            'image_url' => $url,
                            'is_primary' => $index == 0,
                            'sort_order' => $index,
                        ]);
                    }
                }
            }

            if ($request->hasFile('product_images')) {
                foreach ($request->file('product_images') as $index => $file) {

                    // Throws \RuntimeException if the upload is invalid or the
                    // storage write fails — caught below, which rolls back the
                    // DB transaction and cleans up any files already stored.
                    $path = $this->storeUploadedFile($file, 'product_images');

                    $uploadedPaths[] = $path;

                    MI_Product_Image::create([
                        'product_id' => $product->product_id,
                        'image_type' => 'upload',
                        'image_path' => $path,
                        'is_primary' => empty($request->image_links) && $index == 0,
                        'sort_order' => $index,
                    ]);
                }
            }
            /*
            |--------------------------------------------------------------------------
            | Convert Google Drive Image Link
            |--------------------------------------------------------------------------
            */

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
                .'-'
                .$subCategoryCode
                .'-'
                .$collectionCode
                .'-'
                .$subsubCategory
                .'-'
                .str_pad($product->product_id, 4, '0', STR_PAD_LEFT);

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

            foreach ($uploadedPaths as $path) {
                if (! empty($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            Log::error('Product save failed: '.$e->getMessage(), [
                'exception' => $e,
                'input' => $request->except('product_images'),
            ]);

            $errorMessage = $e->getMessage() ?: 'Something went wrong while saving the product. Please try again or contact support.';

            return back()
                ->withInput()
                ->withErrors([
                    'error' => $errorMessage,
                ])
                ->with('error', $errorMessage);
        }
    }

    public function edit(MI_Product $product): View
    {
        $product->load(['category', 'subCategory', 'productType', 'collection', 'images']);
        $categories = MI_Category::orderBy('name')->get();
        $subCategories = MI_SubCategory::orderBy('name')->get();
        $productTypes = MI_ProductType::orderBy('name')->get();
        $collections = MI_Collection::orderBy('name')->get();

        return view('mi_app.designer_module.edit', compact('product', 'categories', 'subCategories', 'productTypes', 'collections'));
    }

    public function update(Request $request, MI_Product $product): RedirectResponse
    {
        $ownedImage = Rule::exists('mi_product_images', 'id')->where('product_id', $product->getKey());
        $rules = [
            'item_name' => 'required|string|max:255',
            'item_code' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('mi_products', 'item_code')->ignore($product)],
            'description' => 'sometimes|nullable|string|max:20000',
            'type_of_sample' => 'required|string|max:255',
            'designed_by' => 'sometimes|nullable|string|max:255',
            'category_id' => 'required|integer|exists:mi_categories,id',
            'sub_category_id' => 'sometimes|required|integer|exists:mi_sub_categories,id',
            'product_type_id' => 'sometimes|nullable|integer|exists:mi_product_types,id',
            'collection_id' => 'sometimes|nullable|integer|exists:mi_collections,id',
            'materials' => 'required|array|min:1|max:100',
            'materials.*' => 'required|string|max:255',
            'color' => 'sometimes|nullable|array|max:100',
            'color.*' => 'required|string|max:255',
            'type' => 'sometimes|nullable|string|max:255',
            'price' => 'sometimes|nullable|numeric|min:0|max:9999999999.99',
            'purchase_cost' => 'sometimes|nullable|numeric|min:0|max:9999999999.99',
            'image_links' => 'nullable|array|max:30',
            'image_links.*' => 'nullable|url:http,https|max:1000',
            'product_images' => 'nullable|array|max:30',
            'product_images.*' => 'file|image|mimes:jpeg,png,jpg,webp|max:20480',
            'remove_image_ids' => 'nullable|array|max:100',
            'remove_image_ids.*' => ['integer', 'distinct', $ownedImage],
            'primary_image_id' => ['nullable', 'integer', $ownedImage],
            'image_order' => 'nullable|array|max:100',
            'image_order.*' => ['integer', 'distinct', $ownedImage],
        ];
        foreach (['product', 'carton'] as $prefix) {
            foreach (['height', 'width', 'length', 'depth'] as $dimension) {
                $rules[$prefix.'_'.$dimension] = 'sometimes|nullable|numeric|min:0|max:99999999.99';
            }
        }
        $validated = $request->validate($rules);
        $removeIds = array_map('intval', $validated['remove_image_ids'] ?? []);
        if (in_array((int) ($validated['primary_image_id'] ?? 0), $removeIds, true)) {
            throw ValidationException::withMessages(['primary_image_id' => 'Select an image that is not marked for removal.']);
        }
        $controls = ['image_links', 'product_images', 'remove_image_ids', 'primary_image_id', 'image_order'];
        $attributes = Arr::except($validated, $controls);
        $attributes['materials'] = $this->normalizeArrayInput($attributes['materials']);
        if (array_key_exists('color', $attributes)) {
            $attributes['color'] = $this->normalizeArrayInput($attributes['color']);
        }
        $uploadedPaths = [];
        $removedPaths = [];

        try {
            DB::transaction(function () use ($product, $attributes, $validated, $request, $removeIds, &$uploadedPaths, &$removedPaths): void {
                $lockedProduct = MI_Product::whereKey($product->getKey())->lockForUpdate()->firstOrFail();
                $validatedTaxonomy = array_replace($lockedProduct->only(['category_id', 'sub_category_id', 'product_type_id', 'collection_id']), $attributes);
                $this->validateProductTaxonomy($validatedTaxonomy);
                $lockedProduct->fill($attributes)->save();
                $images = $lockedProduct->images()->lockForUpdate()->get();
                $imageIds = $images->modelKeys();
                $submittedIds = array_merge($removeIds, $validated['image_order'] ?? [], isset($validated['primary_image_id']) ? [$validated['primary_image_id']] : []);
                foreach ($submittedIds as $imageId) {
                    if (! in_array((int) $imageId, $imageIds, true)) {
                        throw ValidationException::withMessages(['images' => 'An image has changed since this form was opened. Reload and try again.']);
                    }
                }
                foreach ($images as $image) {
                    if (in_array($image->getKey(), $removeIds, true)) {
                        if ($image->image_type === 'upload' && $image->image_path) {
                            $removedPaths[] = $image->image_path;
                        }
                        $image->delete();
                    }
                }
                $images = $images->reject(fn (MI_Product_Image $image): bool => in_array($image->getKey(), $removeIds, true))->values();
                $nextOrder = (int) ($images->max('sort_order') ?? -1) + 1;
                foreach ($validated['image_links'] ?? [] as $url) {
                    if (! $url || $images->contains('image_url', $url)) {
                        continue;
                    }
                    $images->push($lockedProduct->images()->create([
                        'image_type' => 'url', 'image_url' => $url, 'is_primary' => false, 'sort_order' => $nextOrder++,
                    ]));
                }
                foreach ($request->file('product_images', []) as $file) {
                    $path = $this->storeUploadedFile($file, 'product_images');
                    $uploadedPaths[] = $path;
                    $images->push($lockedProduct->images()->create([
                        'image_type' => 'upload', 'image_path' => $path, 'is_primary' => false, 'sort_order' => $nextOrder++,
                    ]));
                }
                if (! empty($validated['image_order'])) {
                    $order = array_map('intval', $validated['image_order']);
                    $images = $images->sortBy(fn (MI_Product_Image $image): int => ($position = array_search($image->getKey(), $order, true)) === false ? count($order) + $image->sort_order : $position)->values();
                    foreach ($images as $position => $image) {
                        $image->sort_order = $position;
                        $image->save();
                    }
                }
                $primaryId = $validated['primary_image_id'] ?? $images->firstWhere('is_primary', true)?->getKey() ?? $images->first()?->getKey();
                $lockedProduct->images()->update(['is_primary' => false]);
                if ($primaryId !== null) {
                    $lockedProduct->images()->whereKey($primaryId)->update(['is_primary' => true]);
                }
                DB::afterCommit(function () use ($removedPaths): void {
                    $this->deleteProductImageFiles($removedPaths);
                });
            });
        } catch (UniqueConstraintViolationException $exception) {
            $this->deleteProductImageFiles($uploadedPaths);
            throw ValidationException::withMessages(['item_code' => 'The item code has already been taken.']);
        } catch (ValidationException $exception) {
            $this->deleteProductImageFiles($uploadedPaths);
            throw $exception;
        } catch (\Throwable $exception) {
            $this->deleteProductImageFiles($uploadedPaths);
            report($exception);

            return back()->withInput()->withErrors(['error' => 'Unable to save the product. Your existing data has been preserved. Please try again.']);
        }

        return redirect()->route('mi_app.edit', $product)->with('success', 'Product updated successfully!');
    }

    /** @param array<string, mixed> $validated */
    private function validateProductTaxonomy(array $validated): void
    {
        if (! empty($validated['sub_category_id'])) {

            $subCategory = MI_SubCategory::findOrFail(
                $validated['sub_category_id']
            );

            if ((int) $subCategory->category_id !== (int) $validated['category_id']) {
                throw ValidationException::withMessages([
                    'sub_category_id' => 'The selected sub category does not belong to the selected category.',
                ]);
            }
        }

        if (! empty($validated['product_type_id'])) {

            if (empty($validated['sub_category_id'])) {
                throw ValidationException::withMessages([
                    'product_type_id' => 'Please select a sub category first.',
                ]);
            }

            $productType = MI_ProductType::findOrFail(
                $validated['product_type_id']
            );

            if (
                (int) $productType->sub_category_id !==
                (int) $validated['sub_category_id']
            ) {
                throw ValidationException::withMessages([
                    'product_type_id' => 'The selected sub sub category does not belong to the selected sub category.',
                ]);
            }
        }

        if (! empty($validated['collection_id'])) {

            if (empty($validated['product_type_id'])) {
                throw ValidationException::withMessages([
                    'collection_id' => 'Please select a sub sub category first.',
                ]);
            }

            $collection = MI_Collection::findOrFail(
                $validated['collection_id']
            );

            if (
                (int) $collection->product_type_id !==
                (int) $validated['product_type_id']
            ) {
                throw ValidationException::withMessages([
                    'collection_id' => 'The selected collection does not belong to the selected sub sub category.',
                ]);
            }
        }

    }

    /** @param list<string> $paths */
    private function deleteProductImageFiles(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            try {
                if (! str_starts_with($path, 'product_images/') || str_contains($path, '..') || str_contains($path, '\\')) {
                    continue;
                }
                if (MI_Product_Image::where('image_path', $path)->exists() || MI_Product::where('product_file', $path)->exists()) {
                    continue;
                }
                if (! Storage::disk('public')->delete($path)) {
                    Log::warning('Unable to delete unused MI product image', ['path' => $path]);
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
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
            'images',
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

        return view('mi_app.designer_module.taxonomy_edit', [
            'item' => $item,
            'entityType' => $type,
            'categories' => MI_Category::all(),
            'subCategories' => MI_SubCategory::all(),
            'productTypes' => MI_ProductType::all(),
        ]);
    }

    public function taxonomy_update(Request $request, $type, $id)
    {
        switch ($type) {
            case 'category':
                $item = MI_Category::findOrFail($id);
                $request->validate([
                    'name' => 'required|string|max:255|unique:mi_categories,name,'.$item->id,
                ]);
                break;
            case 'sub_category':
                $item = MI_SubCategory::findOrFail($id);
                $request->validate([
                    'name' => 'required|string|max:255|unique:mi_sub_categories,name,'.$item->id,
                ]);
                break;
            case 'product_type':
                $item = MI_ProductType::findOrFail($id);
                $request->validate([
                    'name' => 'required|string|max:255|unique:mi_product_types,name,'.$item->id,
                ]);
                break;
            case 'collection':
                $item = MI_Collection::findOrFail($id);
                $request->validate([
                    'name' => 'required|string|max:255|unique:mi_collections,name,'.$item->id,
                ]);
                break;
            default:
                abort(404, 'Invalid taxonomy type.');
        }

        $item->update($request->only('name', 'description'));

        return redirect()
            ->route('mi_app.settings')
            ->with('success', ucfirst(str_replace('_', ' ', $type)).' updated successfully.');
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

        $item->delete();

        return redirect()->route('mi_app.settings')
            ->with('success', 'Deleted successfully.');
    }
}
