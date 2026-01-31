<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::active()->ordered();

        if ($request->boolean('roots_only')) {
            $query->roots();
        }

        if ($request->boolean('with_children')) {
            $query->with('allChildren');
        }

        $categories = $query->withCount(['products' => fn($q) => $q->published()])->get();

        return response()->json([
            'data' => $categories->map(fn ($cat) => $this->formatCategory($cat, $request->boolean('with_children'))),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->active()
            ->with(['parent', 'children' => fn($q) => $q->active()->ordered()])
            ->withCount(['products' => fn($q) => $q->published()])
            ->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'image' => $category->image,
                'icon' => $category->icon,
                'products_count' => $category->products_count,
                'breadcrumb' => collect($category->breadcrumb)->map(fn ($c) => [
                    'name' => $c->name,
                    'slug' => $c->slug,
                ]),
                'parent' => $category->parent ? [
                    'name' => $category->parent->name,
                    'slug' => $category->parent->slug,
                ] : null,
                'children' => $category->children->map(fn ($child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'image' => $child->image,
                ]),
            ],
        ]);
    }

    public function products(Request $request, string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)->active()->firstOrFail();
        
        // Get products from this category and all descendants
        $categoryIds = $category->getAllDescendantIds();

        $products = Product::published()
            ->whereIn('category_id', $categoryIds)
            ->with(['vendor:id,business_name,slug', 'images' => fn($q) => $q->where('is_primary', true)])
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
                'vendor' => $product->vendor ? [
                    'business_name' => $product->vendor->business_name,
                    'slug' => $product->vendor->slug,
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

    protected function formatCategory(Category $category, bool $withChildren = false): array
    {
        $data = [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'image' => $category->image,
            'icon' => $category->icon,
            'products_count' => $category->products_count,
        ];

        if ($withChildren && $category->relationLoaded('allChildren')) {
            $data['children'] = $category->allChildren->map(fn ($child) => $this->formatCategory($child, true));
        }

        return $data;
    }
}
