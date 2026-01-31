<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Services\StatelessAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stateless Wishlist API controller.
 */
class WishlistController extends Controller
{
    public function __construct(
        protected StatelessAuthService $authService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $this->authService->getUserId($request);

        $wishlistItems = Wishlist::forUser($userId)
            ->with(['product', 'variant'])
            ->get();

        return response()->json([
            'data' => $wishlistItems->map(fn ($item) => [
                'id' => $item->id,
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'slug' => $item->product->slug,
                    'primary_image_url' => $item->product->primary_image_url,
                    'base_price' => $item->product->base_price,
                    'compare_at_price' => $item->product->compare_at_price,
                    'is_in_stock' => $item->product->isInStock(),
                ],
                'variant' => $item->variant ? [
                    'id' => $item->variant->id,
                    'display_name' => $item->variant->display_name,
                    'price' => $item->variant->price,
                ] : null,
                'added_at' => $item->created_at->toISOString(),
            ]),
        ]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $userId = $this->authService->getUserId($request);

        $added = Wishlist::toggle(
            $userId,
            $validated['product_id'],
            $validated['variant_id'] ?? null
        );

        return response()->json([
            'message' => $added ? 'Added to wishlist' : 'Removed from wishlist',
            'in_wishlist' => $added,
        ]);
    }

    public function remove(Request $request, int $product): JsonResponse
    {
        $userId = $this->authService->getUserId($request);

        Wishlist::forUser($userId)
            ->where('product_id', $product)
            ->delete();

        return response()->json([
            'message' => 'Removed from wishlist',
        ]);
    }
}
