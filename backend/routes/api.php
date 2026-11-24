<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\SellerController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('resend-verification', [AuthController::class, 'resendVerification']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me'])->middleware('auth.api');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

Route::prefix('public')->group(function () {
    Route::get('home', [PublicController::class, 'home']);
    Route::get('categories', [PublicController::class, 'categories']);
    Route::get('products', [PublicController::class, 'products']);
    Route::get('products/{slug}', [PublicController::class, 'productDetail']);
    Route::get('stores', [PublicController::class, 'stores']);
    Route::get('stores/{slug}', [PublicController::class, 'storeDetail']);
});

Route::prefix('payments')->group(function () {
    Route::get('methods', [PaymentController::class, 'methods']);
    Route::post('doku/notification', [PaymentController::class, 'dokuNotification']);
});

Route::middleware('auth.api')->group(function () {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::patch('notifications/read-all', [NotificationController::class, 'readAll']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'read']);
});

Route::middleware(['auth.api', 'role:CUSTOMER'])->prefix('customer')->group(function () {
    Route::get('cart', [CustomerController::class, 'getCart']);
    Route::post('cart', [CustomerController::class, 'addToCart']);
    Route::patch('cart/{itemId}', [CustomerController::class, 'updateCartItem']);
    Route::delete('cart/{itemId}', [CustomerController::class, 'removeCartItem']);

    Route::get('wishlist', [CustomerController::class, 'getWishlist']);
    Route::post('wishlist', [CustomerController::class, 'addWishlist']);
    Route::delete('wishlist/{productId}', [CustomerController::class, 'removeWishlist']);

    Route::get('addresses', [CustomerController::class, 'listAddresses']);
    Route::post('addresses', [CustomerController::class, 'createAddress']);
    Route::patch('addresses/{id}', [CustomerController::class, 'updateAddress']);
    Route::delete('addresses/{id}', [CustomerController::class, 'deleteAddress']);

    Route::post('checkout', [CustomerController::class, 'checkout']);
    Route::get('orders', [CustomerController::class, 'listOrders']);
    Route::get('orders/{id}', [CustomerController::class, 'getOrder']);
    Route::post('orders/{id}/cancel', [CustomerController::class, 'cancelOrder']);

    Route::get('orders/{id}/payment', [CustomerController::class, 'getPayment']);
    Route::post('orders/{id}/payment/channel', [CustomerController::class, 'choosePaymentChannel']);
    Route::post('orders/{id}/payment/retry', [CustomerController::class, 'retryPayment']);
    Route::post('orders/{id}/payment/simulate', [CustomerController::class, 'simulatePayment']);

    Route::get('profile', [CustomerController::class, 'getProfile']);
    Route::patch('profile', [CustomerController::class, 'updateProfile']);
    Route::patch('profile/password', [CustomerController::class, 'changePassword']);
});

Route::middleware(['auth.api', 'role:SELLER'])->prefix('seller')->group(function () {
    Route::get('dashboard', [SellerController::class, 'dashboard'])->middleware('store');

    Route::get('store', [SellerController::class, 'getStore']);
    Route::post('store', [SellerController::class, 'createStore']);
    Route::patch('store', [SellerController::class, 'updateStore'])->middleware('store');
    Route::post('store/{kind}', [SellerController::class, 'uploadStoreImage'])
        ->whereIn('kind', ['logo', 'banner'])
        ->middleware('store');

    Route::get('products', [SellerController::class, 'listProducts'])->middleware('store');
    Route::post('products', [SellerController::class, 'createProduct'])->middleware('store');
    Route::get('products/{id}', [SellerController::class, 'getProduct'])->middleware('store');
    Route::patch('products/{id}', [SellerController::class, 'updateProduct'])->middleware('store');
    Route::delete('products/{id}', [SellerController::class, 'deleteProduct'])->middleware('store');
    Route::post('products/{id}/images', [SellerController::class, 'addProductImages'])->middleware('store');
    Route::delete('products/{id}/images/{imageId}', [SellerController::class, 'deleteProductImage'])->middleware('store');

    Route::get('orders', [SellerController::class, 'listOrders'])->middleware('store');
    Route::get('orders/{id}', [SellerController::class, 'getOrder'])->middleware('store');
    Route::patch('orders/{id}/status', [SellerController::class, 'updateOrderStatus'])->middleware('store');
    Route::post('orders/{id}/cod-paid', [SellerController::class, 'markCodPaid'])->middleware('store');
});

Route::middleware(['auth.api', 'role:SUPERADMIN'])->prefix('admin')->group(function () {
    Route::get('dashboard', [AdminController::class, 'dashboard']);

    Route::get('users', [AdminController::class, 'listUsers']);
    Route::get('users/{id}', [AdminController::class, 'getUser']);
    Route::patch('users/{id}/status', [AdminController::class, 'setUserStatus']);

    Route::get('categories', [AdminController::class, 'listCategories']);
    Route::post('categories', [AdminController::class, 'createCategory']);
    Route::patch('categories/{id}', [AdminController::class, 'updateCategory']);
    Route::delete('categories/{id}', [AdminController::class, 'deleteCategory']);

    Route::get('stores', [AdminController::class, 'listStores']);
    Route::patch('stores/{id}/status', [AdminController::class, 'setStoreStatus']);

    Route::get('products', [AdminController::class, 'listProducts']);
    Route::delete('products/{id}', [AdminController::class, 'deleteProduct']);

    Route::get('orders', [AdminController::class, 'listOrders']);
    Route::get('orders/{id}', [AdminController::class, 'getOrder']);
    Route::get('payments', [AdminController::class, 'listPayments']);
    Route::get('payment-notifications', [PaymentController::class, 'notifications']);

    Route::get('banners', [AdminController::class, 'listBanners']);
    Route::post('banners', [AdminController::class, 'createBanner']);
    Route::post('banners/upload', [AdminController::class, 'uploadBannerImage']);
    Route::patch('banners/{id}', [AdminController::class, 'updateBanner']);
    Route::delete('banners/{id}', [AdminController::class, 'deleteBanner']);

    Route::get('activity', [AdminController::class, 'activity']);
    Route::get('notifications', [AdminController::class, 'notifications']);
});
