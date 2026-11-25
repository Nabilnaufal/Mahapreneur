<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\StoreController as AdminStoreController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Customer\AddressController as CustomerAddressController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\ChatController as CustomerChatController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Customer\WishlistController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Public\CategoryController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\ProductController as PublicProductController;
use App\Http\Controllers\Public\StoreController as PublicStoreController;
use App\Http\Controllers\Seller\ChatController as SellerChatController;
use App\Http\Controllers\Seller\DashboardController as SellerDashboardController;
use App\Http\Controllers\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Seller\StoreController as SellerStoreController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::post('verify-email', [VerifyEmailController::class, 'store']);
    Route::post('resend-verification', [EmailVerificationNotificationController::class, 'store']);
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy']);
    Route::get('me', [AuthenticatedSessionController::class, 'show'])->middleware('auth.api');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store']);
    Route::post('reset-password', [NewPasswordController::class, 'store']);
});

Route::prefix('public')->group(function () {
    Route::get('home', [HomeController::class, 'index']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('products', [PublicProductController::class, 'index']);
    Route::get('products/{slug}', [PublicProductController::class, 'show']);
    Route::get('stores', [PublicStoreController::class, 'index']);
    Route::get('stores/{slug}', [PublicStoreController::class, 'show']);
});

Route::prefix('payments')->group(function () {
    Route::get('methods', [PaymentController::class, 'methods']);
    Route::post('doku/notification', [PaymentController::class, 'dokuNotification']);
});

Route::middleware('auth.api')->group(function () {
    Route::post('feedback', [FeedbackController::class, 'store']);
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::patch('notifications/read-all', [NotificationController::class, 'updateAll']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'update']);
});

Route::middleware(['auth.api', 'role:CUSTOMER'])->prefix('customer')->group(function () {
    Route::get('cart', [CartController::class, 'getCart']);
    Route::post('cart', [CartController::class, 'addToCart']);
    Route::patch('cart/{itemId}', [CartController::class, 'updateCartItem']);
    Route::delete('cart/{itemId}', [CartController::class, 'removeCartItem']);

    Route::get('wishlist', [WishlistController::class, 'getWishlist']);
    Route::post('wishlist', [WishlistController::class, 'addWishlist']);
    Route::delete('wishlist/{productId}', [WishlistController::class, 'removeWishlist']);

    Route::get('addresses', [CustomerAddressController::class, 'listAddresses']);
    Route::post('addresses', [CustomerAddressController::class, 'createAddress']);
    Route::patch('addresses/{id}', [CustomerAddressController::class, 'updateAddress']);
    Route::delete('addresses/{id}', [CustomerAddressController::class, 'deleteAddress']);

    Route::post('checkout', [CheckoutController::class, 'checkout']);
    Route::get('orders', [CustomerOrderController::class, 'index']);
    Route::get('orders/{id}', [CustomerOrderController::class, 'show']);
    Route::post('orders/{id}/cancel', [CustomerOrderController::class, 'cancel']);
    Route::post('orders/{id}/review', [CustomerOrderController::class, 'review']);

    Route::get('orders/{id}/payment', [CustomerOrderController::class, 'payment']);
    Route::post('orders/{id}/payment/channel', [CustomerOrderController::class, 'chooseChannel']);
    Route::post('orders/{id}/payment/retry', [CustomerOrderController::class, 'retryPayment']);
    Route::post('orders/{id}/payment/simulate', [CustomerOrderController::class, 'simulatePayment']);

    Route::get('profile', [CustomerProfileController::class, 'show']);
    Route::patch('profile', [CustomerProfileController::class, 'update']);
    Route::patch('profile/password', [CustomerProfileController::class, 'changePassword']);

    Route::get('chats', [CustomerChatController::class, 'index']);
    Route::post('chats', [CustomerChatController::class, 'store']);
    Route::get('chats/{id}', [CustomerChatController::class, 'show']);
    Route::post('chats/{id}/messages', [CustomerChatController::class, 'sendMessage']);
});

Route::middleware(['auth.api', 'role:SELLER'])->prefix('seller')->group(function () {
    Route::get('profile', [CustomerProfileController::class, 'show']);
    Route::patch('profile', [CustomerProfileController::class, 'update']);
    Route::patch('profile/password', [CustomerProfileController::class, 'changePassword']);

    Route::get('dashboard', [SellerDashboardController::class, 'index'])->middleware('store');

    Route::get('store', [SellerStoreController::class, 'show']);
    Route::post('store', [SellerStoreController::class, 'store']);
    Route::patch('store', [SellerStoreController::class, 'update'])->middleware('store');
    Route::post('store/{kind}', [SellerStoreController::class, 'uploadImage'])
        ->whereIn('kind', ['logo', 'banner'])
        ->middleware('store');

    Route::get('products', [SellerProductController::class, 'index'])->middleware('store');
    Route::post('products', [SellerProductController::class, 'store'])->middleware('store');
    Route::get('products/{id}', [SellerProductController::class, 'show'])->middleware('store');
    Route::patch('products/{id}', [SellerProductController::class, 'update'])->middleware('store');
    Route::delete('products/{id}', [SellerProductController::class, 'destroy'])->middleware('store');
    Route::post('products/{id}/images', [SellerProductController::class, 'uploadImages'])->middleware('store');
    Route::delete('products/{id}/images/{imageId}', [SellerProductController::class, 'deleteImage'])->middleware('store');

    Route::get('orders', [SellerOrderController::class, 'index'])->middleware('store');
    Route::get('orders/export', [SellerOrderController::class, 'export'])->middleware('store');
    Route::get('orders/{id}', [SellerOrderController::class, 'show'])->middleware('store');
    Route::patch('orders/{id}/status', [SellerOrderController::class, 'updateStatus'])->middleware('store');

    Route::get('chats', [SellerChatController::class, 'index']);
    Route::post('chats', [SellerChatController::class, 'store']);
    Route::get('chats/{id}', [SellerChatController::class, 'show']);
    Route::post('chats/{id}/messages', [SellerChatController::class, 'sendMessage']);
});

Route::middleware(['auth.api', 'role:SUPERADMIN'])->prefix('admin')->group(function () {
    Route::get('dashboard', [AdminDashboardController::class, 'index']);

    Route::get('users', [AdminUserController::class, 'index']);
    Route::get('users/{id}', [AdminUserController::class, 'show']);
    Route::patch('users/{id}/status', [AdminUserController::class, 'updateStatus']);

    Route::get('categories', [AdminCategoryController::class, 'index']);
    Route::post('categories', [AdminCategoryController::class, 'store']);
    Route::patch('categories/{id}', [AdminCategoryController::class, 'update']);
    Route::delete('categories/{id}', [AdminCategoryController::class, 'destroy']);

    Route::get('stores', [AdminStoreController::class, 'index']);
    Route::patch('stores/{id}/status', [AdminStoreController::class, 'updateStatus']);

    Route::get('products', [AdminProductController::class, 'index']);
    Route::delete('products/{id}', [AdminProductController::class, 'destroy']);

    Route::get('orders', [AdminOrderController::class, 'index']);
    Route::get('orders/{id}', [AdminOrderController::class, 'show']);
    Route::get('payments', [AdminPaymentController::class, 'index']);
    Route::get('payment-notifications', [PaymentController::class, 'notifications']);

    Route::get('banners', [BannerController::class, 'index']);
    Route::post('banners', [BannerController::class, 'store']);
    Route::post('banners/upload', [BannerController::class, 'uploadImage']);
    Route::patch('banners/{id}', [BannerController::class, 'update']);
    Route::delete('banners/{id}', [BannerController::class, 'destroy']);

    Route::get('activity', [ActivityController::class, 'index']);
    Route::get('notifications', [ActivityController::class, 'notifications']);
    Route::get('feedback', [FeedbackController::class, 'index']);
});
