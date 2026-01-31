<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessProductImages;
use App\Jobs\SyncProductSearchIndex;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $vendor = $request->attributes->get('vendor');

        $query = Product::where('vendor_id', $vendor->id)
            ->with(['category:id,name', 'images' => fn($q) => $q->where('is_primary', true)])
            ->withCount('variants');

        // Filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $products = $query->paginate(20)->through(fn ($product) => [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'base_price' => $product->base_price,
            'stock_quantity' => $product->total_stock,
            'status' => $product->status,
            'has_variants' => $product->has_variants,
            'variants_count' => $product->variants_count,
            'category' => $product->category?->name,
            'image' => $product->primary_image_url,
            'created_at' => $product->created_at->toISOString(),
        ]);

        return Inertia::render('Vendor/Products/Index', [
            'products' => $products,
            'filters' => $request->only(['status', 'search', 'sort', 'order']),
        ]);
    }

    public function create(Request $request)
    {
        $categories = Category::active()->ordered()->get(['id', 'name', 'parent_id']);

        return Inertia::render('Vendor/Products/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $vendor = $request->attributes->get('vendor');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'track_inventory' => 'boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'allow_backorder' => 'boolean',
            'product_type' => 'required|in:physical,digital,service',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,active',
            'images' => 'nullable|array',
            'images.*' => 'file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        DB::beginTransaction();

        try {
            $product = Product::create([
                'vendor_id' => $vendor->id,
                'category_id' => $validated['category_id'] ?? null,
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']) . '-' . Str::random(6),
                'sku' => $validated['sku'] ?? strtoupper(Str::random(8)),
                'short_description' => $validated['short_description'] ?? null,
                'description' => $validated['description'] ?? null,
                'base_price' => $validated['base_price'],
                'compare_at_price' => $validated['compare_at_price'] ?? null,
                'cost_price' => $validated['cost_price'] ?? null,
                'track_inventory' => $validated['track_inventory'] ?? true,
                'stock_quantity' => $validated['stock_quantity'] ?? 0,
                'low_stock_threshold' => $validated['low_stock_threshold'] ?? 5,
                'allow_backorder' => $validated['allow_backorder'] ?? false,
                'product_type' => $validated['product_type'],
                'weight' => $validated['weight'] ?? null,
                'length' => $validated['length'] ?? null,
                'width' => $validated['width'] ?? null,
                'height' => $validated['height'] ?? null,
                'status' => $validated['status'],
                'published_at' => $validated['status'] === 'active' ? now() : null,
            ]);

            // Handle image uploads
            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $file) {
                    $path = $file->store("products/{$product->id}", 'public');
                    $imagePaths[] = $path;
                    
                    // Create initial record (will be updated by job)
                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => $path,
                        'is_primary' => $index === 0,
                        'sort_order' => $index,
                    ]);
                }

                // Queue image processing (resizing, thumbnails, optimization)
                ProcessProductImages::dispatch($product, $imagePaths);
            }

            DB::commit();

            // Queue search index sync
            SyncProductSearchIndex::dispatch($product, 'create');

            return redirect()->route('vendor.products.show', $product)
                ->with('success', 'Product created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Request $request, Product $product)
    {
        $vendor = $request->attributes->get('vendor');

        if ($product->vendor_id !== $vendor->id) {
            abort(403);
        }

        $product->load([
            'category:id,name',
            'images',
            'options.values',
            'variants',
        ]);

        return Inertia::render('Vendor/Products/Show', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'base_price' => $product->base_price,
                'compare_at_price' => $product->compare_at_price,
                'cost_price' => $product->cost_price,
                'stock_quantity' => $product->stock_quantity,
                'track_inventory' => $product->track_inventory,
                'allow_backorder' => $product->allow_backorder,
                'has_variants' => $product->has_variants,
                'product_type' => $product->product_type,
                'status' => $product->status,
                'category' => $product->category,
                'images' => $product->images,
                'options' => $product->options,
                'variants' => $product->variants,
                'view_count' => $product->view_count,
                'sold_count' => $product->sold_count,
                'average_rating' => $product->average_rating,
                'review_count' => $product->review_count,
                'created_at' => $product->created_at->toISOString(),
            ],
        ]);
    }

    public function edit(Request $request, Product $product)
    {
        $vendor = $request->attributes->get('vendor');

        if ($product->vendor_id !== $vendor->id) {
            abort(403);
        }

        $product->load(['category', 'images', 'options.values', 'variants']);
        $categories = Category::active()->ordered()->get(['id', 'name', 'parent_id']);

        return Inertia::render('Vendor/Products/Edit', [
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $vendor = $request->attributes->get('vendor');

        if ($product->vendor_id !== $vendor->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'track_inventory' => 'boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'allow_backorder' => 'boolean',
            'product_type' => 'required|in:physical,digital,service',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,active,inactive',
        ]);

        $wasActive = $product->status === 'active';
        $product->update($validated);

        // Set published_at if transitioning to active
        if (!$wasActive && $validated['status'] === 'active' && !$product->published_at) {
            $product->update(['published_at' => now()]);
        }

        // Queue search index sync
        SyncProductSearchIndex::dispatch($product, 'update');

        return redirect()->route('vendor.products.show', $product)
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Request $request, Product $product)
    {
        $vendor = $request->attributes->get('vendor');

        if ($product->vendor_id !== $vendor->id) {
            abort(403);
        }

        // Queue removal from search index before deleting
        SyncProductSearchIndex::dispatch($product, 'delete');

        $product->delete();

        return redirect()->route('vendor.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    /**
     * Add variants to a product
     */
    public function storeVariants(Request $request, Product $product)
    {
        $vendor = $request->attributes->get('vendor');

        if ($product->vendor_id !== $vendor->id) {
            abort(403);
        }

        $validated = $request->validate([
            'options' => 'required|array|min:1',
            'options.*.name' => 'required|string|max:100',
            'options.*.values' => 'required|array|min:1',
            'options.*.values.*' => 'required|string|max:100',
            'variants' => 'required|array|min:1',
            'variants.*.option_values' => 'required|array',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.sku' => 'nullable|string|max:100',
            'variants.*.stock_quantity' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            // Remove existing options and variants
            $product->options()->delete();
            $product->variants()->delete();

            // Create options
            foreach ($validated['options'] as $index => $optionData) {
                $option = ProductOption::create([
                    'product_id' => $product->id,
                    'name' => $optionData['name'],
                    'sort_order' => $index,
                ]);

                foreach ($optionData['values'] as $valueIndex => $value) {
                    ProductOptionValue::create([
                        'product_option_id' => $option->id,
                        'value' => $value,
                        'sort_order' => $valueIndex,
                    ]);
                }
            }

            // Create variants
            foreach ($validated['variants'] as $index => $variantData) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $variantData['sku'] ?? strtoupper(Str::random(10)),
                    'price' => $variantData['price'],
                    'stock_quantity' => $variantData['stock_quantity'] ?? 0,
                    'option_values' => $variantData['option_values'],
                    'sort_order' => $index,
                ]);
            }

            // Mark product as having variants
            $product->update(['has_variants' => true]);

            DB::commit();

            return back()->with('success', 'Variants added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
