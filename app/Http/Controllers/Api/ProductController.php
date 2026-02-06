<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::published()
            ->with(['vendor:id,business_name,slug', 'category:id,name,slug', 'images' => fn($q) => $q->where('is_primary', true)])
            ->withCount('variants');

        // Filters
        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('vendor')) {
            $query->where('vendor_id', $request->vendor);
        }

        if ($request->has('min_price')) {
            $query->where('base_price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('base_price', '<=', $request->max_price);
        }

        if ($request->boolean('in_stock')) {
            $query->inStock();
        }

        if ($request->boolean('featured')) {
            $query->featured();
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        
        switch ($sortField) {
            case 'price':
                $query->orderBy('base_price', $sortOrder);
                break;
            case 'name':
                $query->orderBy('name', $sortOrder);
                break;
            case 'popularity':
                $query->orderBy('sold_count', $sortOrder);
                break;
            case 'trending':
                $query->orderBy('view_count', $sortOrder);
                break;
            case 'rating':
                $query->orderBy('average_rating', $sortOrder);
                break;
            default:
                $query->orderBy('created_at', $sortOrder);
        }

        $products = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $products->map(fn ($product) => $this->formatProduct($product)),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->published()
            ->with([
                'vendor:id,business_name,slug,logo,description',
                'category:id,name,slug',
                'images',
                'options.values',
                'activeVariants',
                'approvedReviews' => fn($q) => $q->latest()->take(10),
            ])
            ->firstOrFail();

        $product->incrementViewCount();

        return response()->json([
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'base_price' => $product->base_price,
                'compare_at_price' => $product->compare_at_price,
                'current_price' => $product->current_price,
                'price_range' => $product->price_range,
                'has_discount' => $product->hasDiscount(),
                'discount_percentage' => $product->discount_percentage,
                'is_in_stock' => $product->isInStock(),
                'stock_quantity' => $product->total_stock,
                'has_variants' => $product->has_variants,
                'product_type' => $product->product_type,
                'average_rating' => $product->average_rating,
                'review_count' => $product->review_count,
                'vendor' => $product->vendor,
                'category' => $product->category,
                'images' => $product->images->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => $img->url,
                    'alt_text' => $img->alt_text,
                    'is_primary' => $img->is_primary,
                ]),
                'options' => $product->options->map(fn ($opt) => [
                    'id' => $opt->id,
                    'name' => $opt->name,
                    'values' => $opt->values->map(fn ($val) => [
                        'id' => $val->id,
                        'value' => $val->value,
                        'label' => $val->display_label,
                        'color_code' => $val->color_code,
                    ]),
                ]),
                'variants' => $product->activeVariants->map(fn ($var) => [
                    'id' => $var->id,
                    'sku' => $var->sku,
                    'name' => $var->display_name,
                    'price' => $var->price,
                    'compare_at_price' => $var->compare_at_price,
                    'stock_quantity' => $var->stock_quantity,
                    'is_in_stock' => $var->isInStock(),
                    'option_values' => $var->option_values,
                ]),
                'reviews' => $product->approvedReviews->map(fn ($rev) => [
                    'id' => $rev->id,
                    'customer_name' => $rev->customer_name,
                    'rating' => $rev->rating,
                    'title' => $rev->title,
                    'comment' => $rev->comment,
                    'is_verified_purchase' => $rev->is_verified_purchase,
                    'created_at' => $rev->created_at->toISOString(),
                ]),
            ],
        ]);
    }

    public function featured(): JsonResponse
    {
        $products = Product::published()
            ->featured()
            ->with(['vendor:id,business_name,slug', 'images' => fn($q) => $q->where('is_primary', true)])
            ->take(12)
            ->get();

        return response()->json([
            'data' => $products->map(fn ($product) => $this->formatProduct($product)),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $products = Product::published()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('short_description', 'like', "%{$query}%");
            })
            ->with(['vendor:id,business_name,slug', 'images' => fn($q) => $q->where('is_primary', true)])
            ->take(10)
            ->get();

        return response()->json([
            'data' => $products->map(fn ($product) => $this->formatProduct($product)),
        ]);
    }

    protected function formatProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'primary_image_url' => $product->primary_image_url,
            'base_price' => $product->base_price,
            'compare_at_price' => $product->compare_at_price,
            'average_rating' => $product->average_rating,
            'review_count' => $product->review_count,
            'is_in_stock' => $product->isInStock(),
            'is_featured' => $product->is_featured,
            'vendor' => $product->vendor ? [
                'business_name' => $product->vendor->business_name,
                'slug' => $product->vendor->slug,
            ] : null,
            'category' => $product->category ? [
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ] : null,
        ];
    }
}
