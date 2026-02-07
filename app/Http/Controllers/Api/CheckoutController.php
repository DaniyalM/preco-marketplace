<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\StatelessAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __construct(
        protected StatelessAuthService $authService
    ) {}

    /**
     * List supported blockchain networks for payment (no wallet addresses).
     */
    public function blockchainNetworks(Request $request): JsonResponse
    {
        $networks = config('blockchain.networks');
        $list = [];
        foreach ($networks as $key => $config) {
            if (!empty($config['wallet_address'])) {
                $list[] = [
                    'key' => $key,
                    'name' => $config['name'],
                    'chain_id' => $config['chain_id'],
                    'native_currency' => $config['native_currency'],
                    'decimals' => $config['decimals'] ?? 18,
                ];
            }
        }
        return response()->json(['data' => $list]);
    }

    /**
     * Create an order from the current cart (place order).
     */
    public function placeOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_address' => 'required|array',
            'shipping_address.first_name' => 'required|string|max:100',
            'shipping_address.last_name' => 'required|string|max:100',
            'shipping_address.email' => 'required|email',
            'shipping_address.phone' => 'nullable|string|max:30',
            'shipping_address.address_line_1' => 'required|string|max:255',
            'shipping_address.address_line_2' => 'nullable|string|max:255',
            'shipping_address.city' => 'required|string|max:100',
            'shipping_address.state' => 'nullable|string|max:100',
            'shipping_address.postal_code' => 'required|string|max:20',
            'shipping_address.country' => 'required|string|size:2',
            'payment_method' => 'required|in:blockchain,card',
            'customer_notes' => 'nullable|string|max:1000',
            // For blockchain: which network (optional; uses default from config)
            'payment_network' => 'nullable|string|in:ethereum,polygon,sepolia',
        ]);

        $userId = $this->authService->getUserId($request);
        $cart = Cart::with(['items.product.vendor', 'items.variant'])->where('keycloak_user_id', $userId)->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['error' => 'Cart is empty'], 422);
        }

        foreach ($cart->items as $item) {
            if (!$item->isInStock()) {
                return response()->json([
                    'error' => 'Insufficient stock',
                    'product' => $item->product->name,
                ], 422);
            }
        }

        $shippingAddress = $validated['shipping_address'];
        $paymentMethod = $validated['payment_method'];
        $paymentNetworkKey = $validated['payment_network'] ?? config('blockchain.default_network');

        try {
            $order = DB::transaction(function () use ($cart, $userId, $shippingAddress, $paymentMethod, $paymentNetworkKey) {
                $subtotal = $cart->subtotal;
                $shippingAmount = 0;
                $taxAmount = 0;
                $discountAmount = 0;
                $total = $subtotal + $shippingAmount + $taxAmount - $discountAmount;

                $orderData = [
                    'keycloak_user_id' => $userId,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'payment_method' => $paymentMethod,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'shipping_amount' => $shippingAmount,
                    'tax_amount' => $taxAmount,
                    'total' => $total,
                    'currency' => 'USD',
                    'shipping_address' => $shippingAddress,
                    'billing_address' => $shippingAddress,
                    'customer_notes' => request()->input('customer_notes'),
                ];

                if ($paymentMethod === 'blockchain') {
                    $networks = config('blockchain.networks');
                    $network = $networks[$paymentNetworkKey] ?? $networks[config('blockchain.default_network')] ?? null;
                    if (!$network) {
                        throw new \InvalidArgumentException('Blockchain network not found. Set BLOCKCHAIN_DEFAULT_NETWORK in .env (e.g. sepolia or polygon).');
                    }
                    $walletAddress = $network['wallet_address'] ?? null;
                    $envVars = [
                        'ethereum' => 'BLOCKCHAIN_ETHEREUM_WALLET',
                        'polygon' => 'BLOCKCHAIN_POLYGON_WALLET',
                        'sepolia' => 'BLOCKCHAIN_SEPOLIA_WALLET',
                    ];
                    $envVar = $envVars[$paymentNetworkKey] ?? 'BLOCKCHAIN_*_WALLET';
                    if (!$walletAddress || trim($walletAddress) === '') {
                        throw new \InvalidArgumentException(
                            "Blockchain wallet not configured for this network. Add {$envVar}=0xYourAddress to your .env file (use Sepolia testnet for development)."
                        );
                    }
                    $orderData['payment_network'] = $paymentNetworkKey;
                    $orderData['payment_chain_id'] = $network['chain_id'];
                    $orderData['payment_currency'] = $network['native_currency'];
                }

                $order = Order::create($orderData);

                foreach ($cart->items as $cartItem) {
                    $product = $cartItem->product;
                    $vendor = $product->vendor;
                    $unitPrice = $cartItem->getPrice();
                    $quantity = $cartItem->quantity;
                    $itemSubtotal = $unitPrice * $quantity;
                    $itemDiscount = 0;
                    $itemTax = 0;
                    $itemTotal = $itemSubtotal - $itemDiscount + $itemTax;
                    $commissionRate = (float) ($vendor->commission_rate ?? 0);
                    $commissionAmount = round($itemTotal * ($commissionRate / 100), 2);
                    $vendorPayout = $itemTotal - $commissionAmount;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'vendor_id' => $vendor->id,
                        'product_id' => $product->id,
                        'product_variant_id' => $cartItem->product_variant_id,
                        'product_name' => $product->name,
                        'variant_name' => $cartItem->variant?->display_name,
                        'sku' => $cartItem->variant?->sku ?? $product->sku,
                        'options' => $cartItem->options,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $itemSubtotal,
                        'discount_amount' => $itemDiscount,
                        'tax_amount' => $itemTax,
                        'total' => $itemTotal,
                        'commission_rate' => $commissionRate,
                        'commission_amount' => $commissionAmount,
                        'vendor_payout' => $vendorPayout,
                    ]);

                    if ($cartItem->product_variant_id && $cartItem->variant) {
                        $cartItem->variant->decrementStock($quantity);
                    } else {
                        $cartItem->product->decrement('stock_quantity', $quantity);
                    }
                }

                $cart->clear();

                return $order->fresh(['items']);
            });
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Failed to create order'], 500);
        }

        $response = [
            'data' => $this->formatOrder($order),
        ];

        if ($paymentMethod === 'blockchain') {
            $networks = config('blockchain.networks');
            $network = $networks[$order->payment_network] ?? null;
            $usdRates = config('blockchain.usd_to_crypto');
            $currency = $order->payment_currency ?? 'ETH';
            $rate = $usdRates[$currency] ?? 2500;
            $cryptoAmount = $rate > 0 ? (float) number_format($order->total / $rate, 8, '.', '') : 0;

            $response['data']['blockchain_payment'] = [
                'network' => $order->payment_network,
                'chain_id' => $order->payment_chain_id,
                'currency' => $currency,
                'amount_crypto' => (string) $cryptoAmount,
                'amount_usd' => (float) $order->total,
                'merchant_wallet_address' => $network['wallet_address'] ?? null,
                'explorer_tx_url' => $network['explorer_tx_url'] ?? null,
            ];
        }

        return response()->json($response);
    }

    /**
     * Confirm a crypto payment (user submits tx hash after sending funds).
     */
    public function confirmCryptoPayment(Request $request, Order $order): JsonResponse
    {
        $userId = $this->authService->getUserId($request);

        if ($order->keycloak_user_id !== $userId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($order->payment_method !== 'blockchain') {
            return response()->json(['error' => 'Order is not a blockchain payment'], 422);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['error' => 'Order is already paid'], 422);
        }

        $validated = $request->validate([
            'tx_hash' => 'required|string|max:100',
            'payer_wallet_address' => 'required|string|max:100',
        ]);

        $order->update([
            'payment_reference' => $validated['tx_hash'],
            'payer_wallet_address' => $validated['payer_wallet_address'],
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        return response()->json([
            'message' => 'Payment confirmed',
            'data' => $this->formatOrder($order->fresh()),
        ]);
    }

    protected function formatOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'payment_reference' => $order->payment_reference,
            'payment_network' => $order->payment_network,
            'payment_chain_id' => $order->payment_chain_id,
            'payment_currency' => $order->payment_currency,
            'payer_wallet_address' => $order->payer_wallet_address,
            'subtotal' => (float) $order->subtotal,
            'total' => (float) $order->total,
            'currency' => $order->currency,
            'paid_at' => $order->paid_at?->toISOString(),
            'created_at' => $order->created_at->toISOString(),
        ];
    }
}
