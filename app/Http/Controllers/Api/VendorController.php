<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Vendor::approved()
            ->withCount(['products' => fn($q) => $q->published()]);

        if ($request->boolean('featured')) {
            $query->featured();
        }

        if ($request->has('search')) {
            $query->where('business_name', 'like', "%{$request->search}%");
        }

        $vendors = $query->orderBy('is_featured', 'desc')
            ->orderBy('business_name')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $vendors->map(fn ($vendor) => [
                'id' => $vendor->id,
                'business_name' => $vendor->business_name,
                'slug' => $vendor->slug,
                'logo' => $vendor->logo,
                'banner' => $vendor->banner,
                'description' => $vendor->description,
                'is_featured' => $vendor->is_featured,
                'products_count' => $vendor->products_count,
            ]),
            'meta' => [
                'current_page' => $vendors->currentPage(),
                'last_page' => $vendors->lastPage(),
                'per_page' => $vendors->perPage(),
                'total' => $vendors->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $vendor = Vendor::where('slug', $slug)
            ->approved()
            ->withCount(['products' => fn($q) => $q->published()])
            ->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $vendor->id,
                'business_name' => $vendor->business_name,
                'slug' => $vendor->slug,
                'logo' => $vendor->logo,
                'banner' => $vendor->banner,
                'description' => $vendor->description,
                'website' => $vendor->website,
                'is_featured' => $vendor->is_featured,
                'products_count' => $vendor->products_count,
            ],
        ]);
    }

    public function products(Request $request, string $slug): JsonResponse
    {
        $vendor = Vendor::where('slug', $slug)->approved()->firstOrFail();

        $products = Product::where('vendor_id', $vendor->id)
            ->published()
            ->with(['category:id,name,slug', 'images' => fn($q) => $q->where('is_primary', true)])
            ->orderBy($request->get('sort', 'created_at'), $request->get('order', 'desc'))
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $products->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'primary_image_url' => $product->primary_image_url,
                'base_price' => $product->base_price,
                'compare_at_price' => $product->compare_at_price,
                'average_rating' => $product->average_rating,
                'review_count' => $product->review_count,
                'is_in_stock' => $product->isInStock(),
                'category' => $product->category ? [
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ] : null,
            ]),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }
}
