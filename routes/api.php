<?php

use Illuminate\Support\Facades\Route;

// Import Controller Customer
use App\Http\Controllers\Api\Customer\RegisterController;
use App\Http\Controllers\Api\Customer\LoginController;
use App\Http\Controllers\Api\Customer\LogoutController;
use App\Http\Controllers\Api\Customer\MyOrderController;
use App\Http\Controllers\Api\Customer\MyProfileController;
use App\Http\Controllers\Api\Customer\RatingController;

// Import Controller Public
use App\Http\Controllers\Api\Public\SliderController;
use App\Http\Controllers\Api\Public\CategoryController;
use App\Http\Controllers\Api\Public\ProductController;
use App\Http\Controllers\Api\Public\CartController;
use App\Http\Controllers\Api\Public\RajaOngkirController;
use App\Http\Controllers\Api\Public\CheckoutController;
use App\Http\Controllers\Api\Public\CallbackController;

// ==========================================
// ROUTE GROUP CUSTOMER
// ==========================================
Route::group(['prefix' => 'customer'], function () {
    Route::post('/register', [RegisterController::class, 'store'])->name('customer.register');
    Route::post('/login', [LoginController::class, 'index'])->name('customer.login');
    Route::post('/logout', [LogoutController::class, 'store'])->name('customer.logout');
    Route::get('/my-orders', [MyOrderController::class, 'index'])->name('customer.my-orders');
    Route::get('/my-orders/{snap_token}', [MyOrderController::class, 'show'])->name('customer.my-orders.show');
    Route::get('/profile', [MyProfileController::class, 'index'])->name('customer.profile');
    Route::post('/profile', [MyProfileController::class, 'update'])->name('customer.profile.update');
    Route::post('/ratings', [RatingController::class, 'store'])->name('customer.ratings');
});

// ==========================================
// ROUTE GROUP PUBLIC
// ==========================================
Route::group(['prefix' => 'public'], function () {
    Route::get('/sliders', [SliderController::class, 'index'])->name('public.sliders');
    Route::get('/categories', [CategoryController::class, 'index'])->name('public.categories');
    Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('public.categories.show');

    Route::get('/products-popular', [ProductController::class, 'productsPopular'])->name('public.products.popular');
    Route::get('/products', [ProductController::class, 'index'])->name('public.products');
    Route::get('/products/{slug}', [ProductController::class, 'show'])->name('public.products.show');

    Route::get('/carts', [CartController::class, 'index'])->name('public.carts');
    Route::post('/carts', [CartController::class, 'store'])->name('public.carts.store');
    Route::post('/carts/increment', [CartController::class, 'incrementCart'])->name('public.carts.increment');
    Route::post('/carts/decrement', [CartController::class, 'decrementCart'])->name('public.carts.decrement');
    Route::post('/carts/destroy', [CartController::class, 'destroyCart'])->name('public.carts.destroy');

    Route::get('/search-destination', [RajaOngkirController::class, 'searchDestination'])->name('public.search-destination');
    Route::get('/provinces', [RajaOngkirController::class, 'getProvinces'])->name('public.provinces');
    Route::get('/cities', [RajaOngkirController::class, 'getCities'])->name('public.cities');
    Route::post('/check-ongkir', [RajaOngkirController::class, 'checkOngkir'])->name('public.ongkir');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('public.checkout');
});

// ==========================================
// ROUTE CALLBACK MIDTRANS
// ==========================================
Route::post('/callback', [CallbackController::class, 'store'])->name('public.callback');