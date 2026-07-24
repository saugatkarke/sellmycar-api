<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Public routes — no login required
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes — login required
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Categories
    Route::apiResource('/categories', CategoryController::class);

    // Products
    Route::apiResource('/products', ProductController::class);

    // Cart
    Route::post('/cart/items', [CartController::class, 'store']);
    Route::get('/cart', [CartController::class, 'index']);
    Route::put('/cart/items/{id}', [CartController::class, 'update']);
    Route::delete('/cart/items/{id}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);
    //Checkout
    Route::post('/checkout', [CheckoutController::class, 'store']);
    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
});
