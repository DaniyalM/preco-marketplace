<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\KycController as AdminKycController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Auth\StatelessAuthController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Vendor\OnboardingController;
use App\Http\Controllers\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Vendor\ProductController as VendorProductController;
use App\Http\Controllers\Vendor\SettingsController as VendorSettingsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Authentication Routes (Stateless)
|--------------------------------------------------------------------------
*/

Route::get('/login', [StatelessAuthController::class, 'login'])->name('login');
Route::get('/auth/callback', [StatelessAuthController::class, 'callback'])->name('auth.callback');
Route::post('/auth/refresh', [StatelessAuthController::class, 'refresh'])->name('auth.refresh');
Route::post('/auth/logout', [StatelessAuthController::class, 'logout'])->name('auth.logout');
Route::get('/auth/logout', [StatelessAuthController::class, 'logout'])->name('logout');
Route::get('/auth/me', [StatelessAuthController::class, 'me'])->name('auth.me');

// Debug route - REMOVE IN PRODUCTION
Route::get('/auth/debug', function (\Illuminate\Http\Request $request) {
    $accessToken = $request->cookie('access_token');
    $refreshToken = $request->cookie('refresh_token');
    
    return response()->json([
        'cookies_present' => [
            'access_token' => $accessToken ? 'present (' . strlen($accessToken) . ' chars)' : 'missing',
            'refresh_token' => $refreshToken ? 'present (' . strlen($refreshToken) . ' chars)' : 'missing',
        ],
        'request_attributes' => [
            'user_id' => $request->attributes->get('user_id'),
            'tenant_id' => $request->attributes->get('tenant_id'),
            'keycloak_roles' => $request->attributes->get('keycloak_roles'),
            'keycloak_user' => $request->attributes->get('keycloak_user'),
        ],
        'is_authenticated' => app(\App\Services\StatelessAuthService::class)->isAuthenticated($request),
        'roles' => app(\App\Services\StatelessAuthService::class)->getRoles($request),
    ]);
});

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('welcome');

Route::get('/home', function () {
    return Inertia::render('Home');
})->name('home');

// Public product browsing (SSR for SEO)
Route::get('/products', function () {
    $products = \App\Models\Product::where('status', 'active')
        ->with([
            'vendor:id,business_name,slug',
            'category:id,name,slug',
            'images' => fn($q) => $q->where('is_primary', true),
        ])
        ->orderBy('created_at', 'desc')
        ->paginate(20);

    $categories = \App\Models\Category::active()
        ->withCount('products')
        ->ordered()
        ->get(['id', 'name', 'slug']);

    return Inertia::render('Products/Index', [
        'products' => $products,
        'categories' => $categories,
        'seo' => [
            'title' => 'Products',
            'description' => 'Browse our wide selection of products from verified vendors. Find the best deals on quality items.',
        ],
    ]);
})->name('products.index');

Route::get('/products/{slug}', function ($slug) {
    // Fetch product data for SSR (SEO critical page)
    $product = \App\Models\Product::where('slug', $slug)
        ->where('status', 'active')
        ->with([
            'vendor:id,business_name,slug,logo,description',
            'category:id,name,slug',
            'images' => fn($q) => $q->orderBy('is_primary', 'desc')->orderBy('sort_order'),
            'options.values',
            'variants',
        ])
        ->first();

    if (!$product) {
        abort(404);
    }

    // Prepare SEO meta data
    $seo = [
        'title' => $product->name,
        'description' => $product->short_description ?? \Illuminate\Support\Str::limit(strip_tags($product->description ?? ''), 160),
        'image' => $product->images->first()?->url,
        'type' => 'product',
        'price' => [
            'amount' => $product->base_price,
            'currency' => 'USD',
        ],
        'availability' => $product->isInStock() ? 'in stock' : 'out of stock',
        'brand' => $product->vendor?->business_name,
        'category' => $product->category?->name,
        'rating' => $product->review_count > 0 ? [
            'value' => $product->average_rating,
            'count' => $product->review_count,
        ] : null,
    ];

    return Inertia::render('Products/Show', [
        'slug' => $slug,
        'product' => $product,
        'seo' => $seo,
    ]);
})->name('products.show');

Route::get('/categories', function () {
    $categories = \App\Models\Category::active()
        ->withCount('products')
        ->with('children:id,parent_id,name,slug')
        ->whereNull('parent_id')
        ->ordered()
        ->get();

    return Inertia::render('Categories/Index', [
        'categories' => $categories,
        'seo' => [
            'title' => 'Categories',
            'description' => 'Browse products by category. Find exactly what you\'re looking for in our organized collection.',
        ],
    ]);
})->name('categories.index');

Route::get('/categories/{slug}', function ($slug) {
    $category = \App\Models\Category::where('slug', $slug)
        ->where('is_active', true)
        ->with('children:id,parent_id,name,slug')
        ->firstOrFail();

    $products = \App\Models\Product::where('category_id', $category->id)
        ->where('status', 'active')
        ->with([
            'vendor:id,business_name,slug',
            'images' => fn($q) => $q->where('is_primary', true),
        ])
        ->paginate(20);

    return Inertia::render('Categories/Show', [
        'slug' => $slug,
        'category' => $category,
        'products' => $products,
        'seo' => [
            'title' => $category->name,
            'description' => $category->description ?? "Browse {$category->name} products from verified vendors.",
        ],
    ]);
})->name('categories.show');

Route::get('/vendors', function () {
    $vendors = \App\Models\Vendor::where('status', 'approved')
        ->withCount('products')
        ->orderBy('is_featured', 'desc')
        ->orderBy('created_at', 'desc')
        ->paginate(20);

    return Inertia::render('Vendors/Index', [
        'vendors' => $vendors,
        'seo' => [
            'title' => 'Vendors',
            'description' => 'Discover verified vendors on our marketplace. Shop from trusted sellers with quality products.',
        ],
    ]);
})->name('vendors.index');

Route::get('/vendors/{slug}', function ($slug) {
    $vendor = \App\Models\Vendor::where('slug', $slug)
        ->where('status', 'approved')
        ->firstOrFail();

    $products = \App\Models\Product::where('vendor_id', $vendor->id)
        ->where('status', 'active')
        ->with([
            'category:id,name,slug',
            'images' => fn($q) => $q->where('is_primary', true),
        ])
        ->paginate(20);

    return Inertia::render('Vendors/Show', [
        'slug' => $slug,
        'vendor' => $vendor,
        'products' => $products,
        'seo' => [
            'title' => $vendor->business_name,
            'description' => $vendor->description ?? "Shop products from {$vendor->business_name}. Verified vendor on our marketplace.",
            'image' => $vendor->logo,
        ],
    ]);
})->name('vendors.show');

/*
|--------------------------------------------------------------------------
| Vendor Routes
|--------------------------------------------------------------------------
*/

Route::prefix('vendor')->name('vendor.')->group(function () {
    // Vendor Registration - any authenticated user can start
    // (no role required - they become vendor through onboarding)
    Route::get('/register', [OnboardingController::class, 'index'])->name('register');
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding');
    Route::post('/onboarding/basic-info', [OnboardingController::class, 'storeBasicInfo'])->name('onboarding.store-basic');
    Route::get('/onboarding/address', [OnboardingController::class, 'showAddressForm'])->name('onboarding.address');
    Route::post('/onboarding/address', [OnboardingController::class, 'storeAddress'])->name('onboarding.store-address');
    Route::get('/onboarding/kyc', [OnboardingController::class, 'showKycForm'])->name('onboarding.kyc');
    Route::post('/onboarding/kyc', [OnboardingController::class, 'storeKyc'])->name('onboarding.store-kyc');
    Route::get('/status', [OnboardingController::class, 'status'])->name('status');

    // Dashboard & Management (requires approved vendor)
    Route::middleware(['role:vendor', 'vendor.approved'])->group(function () {
        Route::get('/', [VendorDashboardController::class, 'index'])->name('dashboard');
        
        // Products
        Route::resource('products', VendorProductController::class);
        Route::post('/products/{product}/variants', [VendorProductController::class, 'storeVariants'])->name('products.variants.store');
        
        // Orders
        Route::get('/orders', [VendorOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [VendorOrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/items/{item}/fulfillment', [VendorOrderController::class, 'updateItemFulfillment'])->name('orders.items.fulfillment');
        
        // Settings
        Route::get('/settings', [VendorSettingsController::class, 'index'])->name('settings');
        Route::patch('/settings/profile', [VendorSettingsController::class, 'updateProfile'])->name('settings.profile');
        Route::patch('/settings/address', [VendorSettingsController::class, 'updateAddress'])->name('settings.address');
        Route::post('/settings/logo', [VendorSettingsController::class, 'updateLogo'])->name('settings.logo');
        Route::post('/settings/banner', [VendorSettingsController::class, 'updateBanner'])->name('settings.banner');
        Route::patch('/settings/preferences', [VendorSettingsController::class, 'updateSettings'])->name('settings.preferences');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['role:admin'])->group(function () {
    Route::get('/', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    // Vendors
    Route::get('/vendors', [AdminVendorController::class, 'index'])->name('vendors.index');
    Route::get('/vendors/{vendor}', [AdminVendorController::class, 'show'])->name('vendors.show');
    Route::post('/vendors/{vendor}/approve', [AdminVendorController::class, 'approve'])->name('vendors.approve');
    Route::post('/vendors/{vendor}/reject', [AdminVendorController::class, 'reject'])->name('vendors.reject');
    Route::post('/vendors/{vendor}/suspend', [AdminVendorController::class, 'suspend'])->name('vendors.suspend');
    Route::patch('/vendors/{vendor}/commission', [AdminVendorController::class, 'updateCommission'])->name('vendors.commission');
    Route::post('/vendors/{vendor}/toggle-featured', [AdminVendorController::class, 'toggleFeatured'])->name('vendors.toggle-featured');

    // KYC
    Route::get('/kyc', [AdminKycController::class, 'index'])->name('kyc.index');
    Route::get('/kyc/{kyc}', [AdminKycController::class, 'show'])->name('kyc.show');
    Route::post('/kyc/{kyc}/start-review', [AdminKycController::class, 'startReview'])->name('kyc.start-review');
    Route::post('/kyc/{kyc}/approve', [AdminKycController::class, 'approve'])->name('kyc.approve');
    Route::post('/kyc/{kyc}/reject', [AdminKycController::class, 'reject'])->name('kyc.reject');

    // Categories
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::patch('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
    Route::post('/categories/{category}/toggle-active', [AdminCategoryController::class, 'toggleActive'])->name('categories.toggle-active');
    Route::post('/categories/{category}/toggle-featured', [AdminCategoryController::class, 'toggleFeatured'])->name('categories.toggle-featured');
    Route::post('/categories/reorder', [AdminCategoryController::class, 'reorder'])->name('categories.reorder');

    // Products
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [AdminProductController::class, 'show'])->name('products.show');
    Route::patch('/products/{product}/status', [AdminProductController::class, 'updateStatus'])->name('products.status');
    Route::post('/products/{product}/toggle-featured', [AdminProductController::class, 'toggleFeatured'])->name('products.toggle-featured');
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/products/bulk-action', [AdminProductController::class, 'bulkAction'])->name('products.bulk-action');

    // Orders
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('/orders/{order}/payment-status', [AdminOrderController::class, 'updatePaymentStatus'])->name('orders.payment-status');
    Route::patch('/orders/{order}/tracking', [AdminOrderController::class, 'updateTracking'])->name('orders.tracking');
    Route::post('/orders/{order}/note', [AdminOrderController::class, 'addNote'])->name('orders.note');
});

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['role:customer,vendor,admin'])->group(function () {
    // Cart
    Route::get('/cart', function () {
        return Inertia::render('Cart/Index');
    })->name('cart.index');

    // Checkout
    Route::get('/checkout', function () {
        return Inertia::render('Checkout/Index');
    })->name('checkout.index');

    // Orders
    Route::get('/orders', function () {
        return Inertia::render('Orders/Index');
    })->name('orders.index');

    Route::get('/orders/{order}', function ($order) {
        return Inertia::render('Orders/Show', ['orderId' => $order]);
    })->name('orders.show');

    // Wishlist
    Route::get('/wishlist', function () {
        return Inertia::render('Wishlist/Index');
    })->name('wishlist.index');

    // Profile
    Route::get('/profile', function () {
        return Inertia::render('Profile/Index');
    })->name('profile.index');

    Route::get('/profile/addresses', function () {
        return Inertia::render('Profile/Addresses');
    })->name('profile.addresses');
});
