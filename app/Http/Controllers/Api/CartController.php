<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\StatelessAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stateless Cart API controller.
 * 
 * User ID is extracted from JWT token (via middleware).
 * No sessions or server-side state.
 */
class CartController extends Controller
{
    public function __construct(
        protected StatelessAuthService $authService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $this->authService->getUserId($request);
        
        $cart = Cart::with(['items.product', 'items.variant'])
            ->where('keycloak_user_id', $userId)
            ->first();

        if (!$cart) {
            return response()->json([
                'data' => [
                    'items' => [],
                    'subtotal' => 0,
                    'item_count' => 0,
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'items' => $cart->items->map(fn ($item) => $this->formatCartItem($item)),
                'subtotal' => $cart->subtotal,
                'item_count' => $cart->item_count,
                'coupon_code' => $cart->coupon_code,
            ],
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $userId = $this->authService->getUserId($request);

        // Get or create cart
        $cart = Cart::firstOrCreate(
            ['keycloak_user_id' => $userId],
            ['keycloak_user_id' => $userId]
        );

        // Add item
        $item = $cart->addItem(
            $validated['product_id'],
            $validated['variant_id'] ?? null,
            $validated['quantity']
        );

        $item->load(['product', 'variant']);

        return response()->json([
            'message' => 'Item added to cart',
            'data' => $this->formatCartItem($item),
        ]);
    }

    public function updateItem(Request $request, int $item): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $userId = $this->authService->getUserId($request);
        
        $cart = Cart::where('keycloak_user_id', $userId)->first();

        if (!$cart) {
            return response()->json(['error' => 'Cart not found'], 404);
        }

        $updatedItem = $cart->updateItemQuantity($item, $validated['quantity']);

        if (!$updatedItem) {
            return response()->json([
                'message' => 'Item removed from cart',
                'data' => null,
            ]);
        }

        $updatedItem->load(['product', 'variant']);

        return response()->json([
            'message' => 'Cart updated',
            'data' => $this->formatCartItem($updatedItem),
        ]);
    }

    public function removeItem(Request $request, int $item): JsonResponse
    {
        $userId = $this->authService->getUserId($request);
        
        $cart = Cart::where('keycloak_user_id', $userId)->first();

        if (!$cart) {
            return response()->json(['error' => 'Cart not found'], 404);
        }

        $removed = $cart->removeItem($item);

        return response()->json([
            'message' => $removed ? 'Item removed' : 'Item not found',
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $userId = $this->authService->getUserId($request);
        
        $cart = Cart::where('keycloak_user_id', $userId)->first();

        if ($cart) {
            $cart->clear();
        }

        return response()->json([
            'message' => 'Cart cleared',
        ]);
    }

    protected function formatCartItem(CartItem $item): array
    {
        return [
            'id' => $item->id,
            'quantity' => $item->quantity,
            'product' => [
                'id' => $item->product->id,
                'name' => $item->product->name,
                'slug' => $item->product->slug,
                'primary_image_url' => $item->product->primary_image_url,
            ],
            'variant' => $item->variant ? [
                'id' => $item->variant->id,
                'display_name' => $item->variant->display_name,
                'option_values' => $item->variant->option_values,
            ] : null,
            'price' => $item->getPrice(),
            'compare_at_price' => $item->getCompareAtPrice(),
            'subtotal' => $item->subtotal,
            'is_in_stock' => $item->isInStock(),
            'available_stock' => $item->getAvailableStock(),
        ];
    }
}
