<?php

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MemoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*
|--------------------------------------------------------------------------
| Memory Debug Routes (Development Only)
|--------------------------------------------------------------------------
*/
if (config('app.debug')) {
    Route::prefix('memory')->group(function () {
        Route::get('/', [MemoryController::class, 'index']);           // Full dump
        Route::get('/stats', [MemoryController::class, 'stats']);      // Stats only
        Route::get('/keys', [MemoryController::class, 'keys']);        // List keys
        Route::get('/leaks', [MemoryController::class, 'leaks']);      // Find leaks
        Route::get('/show/{key}', [MemoryController::class, 'show'])->where('key', '.*');
        Route::post('/store', [MemoryController::class, 'store']);     // Test store
        Route::delete('/clear/{key}', [MemoryController::class, 'destroy'])->where('key', '.*');
        Route::delete('/group/{group}', [MemoryController::class, 'destroyGroup']);
        Route::delete('/all', [MemoryController::class, 'destroyAll']);
        Route::post('/cleanup', [MemoryController::class, 'cleanup']); // Cleanup old
        Route::post('/gc', [MemoryController::class, 'gc']);           // Force GC
    });
}

// Public API routes
Route::prefix('public')->group(function () {
    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);
    Route::get('/products/featured', [ProductController::class, 'featured']);
    Route::get('/products/search', [ProductController::class, 'search']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{slug}', [CategoryController::class, 'show']);
    Route::get('/categories/{slug}/products', [CategoryController::class, 'products']);

    // Vendors
    Route::get('/vendors', [VendorController::class, 'index']);
    Route::get('/vendors/{slug}', [VendorController::class, 'show']);
    Route::get('/vendors/{slug}/products', [VendorController::class, 'products']);
});

// Authenticated API routes
Route::middleware(['role:customer,vendor,admin'])->group(function () {
    // User profile
    Route::get('/user/profile', function (Request $request) {
        return response()->json([
            'user' => $request->attributes->get('keycloak_user'),
            'roles' => $request->attributes->get('keycloak_roles'),
        ]);
    });

    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::patch('/cart/items/{item}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{item}', [CartController::class, 'removeItem']);
    Route::delete('/cart', [CartController::class, 'clear']);

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'toggle']);
    Route::delete('/wishlist/{product}', [WishlistController::class, 'remove']);
});
