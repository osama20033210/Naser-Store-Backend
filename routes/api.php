<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\StoreAccountController;
use App\Http\Controllers\StoreCatalogController;
use App\Http\Controllers\StoreOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('store')->middleware('throttle:store')->group(function () {
        Route::get('product-images/{filename}', ProductImageController::class)->where('filename', '[0-9a-f-]{36}\.(jpg|png|webp)');
        Route::get('categories', [StoreCatalogController::class, 'categories']);
        Route::get('products', [StoreCatalogController::class, 'products']);
        Route::get('products/{product}', [StoreCatalogController::class, 'product'])->whereNumber('product');
        Route::post('cart/quote', [StoreCatalogController::class, 'quote'])->middleware('csrf.cookie');
    });
    Route::get('auth/csrf', [AuthController::class, 'csrf'])->middleware('throttle:auth');
    Route::middleware('csrf.cookie')->group(function () {
        Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('auth/register', RegistrationController::class)->middleware('throttle:register');
        Route::middleware(['cookie.jwt', 'throttle:auth'])->group(function () {
            Route::post('auth/refresh', [AuthController::class, 'refresh']);
            Route::post('auth/logout', [AuthController::class, 'logout']);
        });
        Route::middleware(['cookie.jwt', 'auth:api', 'throttle:api'])->group(function () {
            Route::get('auth/me', [AuthController::class, 'me']);
            Route::patch('auth/profile', [AuthController::class, 'profile']);
            Route::prefix('store')->middleware('customer.account')->group(function () {
                Route::get('profile', [StoreAccountController::class, 'show']);
                Route::put('profile', [StoreAccountController::class, 'update']);
                Route::get('orders', [StoreOrderController::class, 'index']);
                Route::post('orders', [StoreOrderController::class, 'store'])->middleware('throttle:checkout');
                Route::get('orders/{order}', [StoreOrderController::class, 'show'])->whereNumber('order');
                Route::post('orders/{order}/cancel', [StoreOrderController::class, 'cancel'])->whereNumber('order');
            });
            Route::middleware('staff.account')->group(function () {
                Route::get('dashboard', DashboardController::class)->middleware('permission:dashboard.view');
                foreach (['categories' => [CategoryController::class, 'category'], 'products' => [ProductController::class, 'product'], 'customers' => [CustomerController::class, 'customer']] as $path => [$controller, $parameter]) {
                    Route::get($path, [$controller, 'index'])->middleware('permission:'.$path.'.view');
                    Route::get($path.'/{'.$parameter.'}', [$controller, 'show'])->middleware('permission:'.$path.'.view');
                    Route::post($path, [$controller, 'store'])->middleware('permission:'.$path.'.create');
                    Route::put($path.'/{'.$parameter.'}', [$controller, 'update'])->middleware('permission:'.$path.'.update');
                    Route::delete($path.'/{'.$parameter.'}', [$controller, 'destroy'])->middleware('permission:'.$path.'.delete');
                }
                Route::get('customers/{customer}/orders', [CustomerController::class, 'orders'])->middleware(['permission:customers.view', 'permission:orders.view']);
                Route::get('orders', [OrderController::class, 'index'])->middleware('permission:orders.view');
                Route::post('orders', [OrderController::class, 'store'])->middleware('permission:orders.create');
                Route::get('orders/{order}', [OrderController::class, 'show'])->middleware('permission:orders.view');
                Route::patch('orders/{order}/status', [OrderController::class, 'status'])->middleware('permission:orders.update-status');
                Route::patch('orders/{order}/payment', [OrderController::class, 'payment'])->middleware('permission:orders.manage-payment');
                Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->middleware('permission:orders.cancel');
                Route::get('inventory/movements', [InventoryController::class, 'movements'])->middleware('permission:inventory.view');
                Route::post('inventory/{product}/adjust', [InventoryController::class, 'adjust'])->middleware('permission:inventory.manage');
            });
        });
    });
});
