<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with([
            'vendor:id,business_name,slug',
            'category:id,name,slug',
            'images' => fn($q) => $q->where('is_primary', true),
        ]);

        // Filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('vendor') && $request->vendor !== 'all') {
            $query->where('vendor_id', $request->vendor);
        }

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category_id', $request->category);
        }

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('stock') && $request->stock !== 'all') {
            if ($request->stock === 'in_stock') {
                $query->where('stock_quantity', '>', 0);
            } elseif ($request->stock === 'low_stock') {
                $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                    ->where('stock_quantity', '>', 0);
            } elseif ($request->stock === 'out_of_stock') {
                $query->where('stock_quantity', 0);
            }
        }

        $products = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'vendor' => $product->vendor ? [
                    'id' => $product->vendor->id,
                    'name' => $product->vendor->business_name,
                ] : null,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
                'image' => $product->images->first()?->path,
                'base_price' => (float) $product->base_price,
                'compare_at_price' => $product->compare_at_price ? (float) $product->compare_at_price : null,
                'stock_quantity' => $product->stock_quantity,
                'status' => $product->status,
                'is_featured' => $product->is_featured,
                'view_count' => $product->view_count,
                'sold_count' => $product->sold_count,
                'average_rating' => (float) $product->average_rating,
                'created_at' => $product->created_at->toISOString(),
            ]);

        // Filter options
        $vendors = Vendor::approved()->get(['id', 'business_name']);
        $categories = Category::active()->ordered()->get(['id', 'name', 'parent_id']);

        // Stats
        $stats = [
            'total' => Product::count(),
            'active' => Product::where('status', 'active')->count(),
            'draft' => Product::where('status', 'draft')->count(),
            'out_of_stock' => Product::where('stock_quantity', 0)->count(),
        ];

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'vendors' => $vendors,
            'categories' => $categories,
            'stats' => $stats,
            'filters' => $request->only(['status', 'vendor', 'category', 'search', 'stock']),
        ]);
    }

    public function show(Product $product)
    {
        $product->load([
            'vendor:id,business_name,email,slug',
            'category:id,name,slug',
            'images',
            'options.values',
            'variants',
            'reviews' => fn($q) => $q->latest()->take(10),
        ]);

        return Inertia::render('Admin/Products/Show', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'vendor' => $product->vendor,
                'category' => $product->category,
                'images' => $product->images,
                'base_price' => (float) $product->base_price,
                'compare_at_price' => $product->compare_at_price ? (float) $product->compare_at_price : null,
                'cost_price' => $product->cost_price ? (float) $product->cost_price : null,
                'stock_quantity' => $product->stock_quantity,
                'low_stock_threshold' => $product->low_stock_threshold,
                'track_inventory' => $product->track_inventory,
                'allow_backorder' => $product->allow_backorder,
                'has_variants' => $product->has_variants,
                'options' => $product->options,
                'variants' => $product->variants,
                'status' => $product->status,
                'is_featured' => $product->is_featured,
                'view_count' => $product->view_count,
                'sold_count' => $product->sold_count,
                'average_rating' => (float) $product->average_rating,
                'review_count' => $product->review_count,
                'reviews' => $product->reviews,
                'weight' => $product->weight,
                'dimensions' => [
                    'length' => $product->length,
                    'width' => $product->width,
                    'height' => $product->height,
                ],
                'meta_title' => $product->meta_title,
                'meta_description' => $product->meta_description,
                'meta_keywords' => $product->meta_keywords,
                'published_at' => $product->published_at?->toISOString(),
                'created_at' => $product->created_at->toISOString(),
                'updated_at' => $product->updated_at->toISOString(),
            ],
        ]);
    }

    public function updateStatus(Request $request, Product $product)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,draft,pending_review',
        ]);

        $product->update([
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'active' && !$product->published_at
                ? now()
                : $product->published_at,
        ]);

        return back()->with('success', 'Product status updated.');
    }

    public function toggleFeatured(Product $product)
    {
        $product->update(['is_featured' => !$product->is_featured]);

        return back()->with('success', $product->is_featured ? 'Product featured.' : 'Product unfeatured.');
    }

    public function destroy(Product $product)
    {
        // Soft delete
        $product->delete();

        return back()->with('success', 'Product deleted successfully.');
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,delete,feature,unfeature',
            'products' => 'required|array|min:1',
            'products.*' => 'exists:products,id',
        ]);

        $products = Product::whereIn('id', $validated['products']);

        switch ($validated['action']) {
            case 'activate':
                $products->update(['status' => 'active']);
                $message = 'Products activated.';
                break;
            case 'deactivate':
                $products->update(['status' => 'inactive']);
                $message = 'Products deactivated.';
                break;
            case 'delete':
                $products->delete();
                $message = 'Products deleted.';
                break;
            case 'feature':
                $products->update(['is_featured' => true]);
                $message = 'Products featured.';
                break;
            case 'unfeature':
                $products->update(['is_featured' => false]);
                $message = 'Products unfeatured.';
                break;
            default:
                $message = 'Action completed.';
        }

        return back()->with('success', $message);
    }
}
