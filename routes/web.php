<?php

use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPainPointsController;
use App\Http\Controllers\Admin\AdminPlanController;
use App\Http\Controllers\Admin\AdminShopifyAppController;
use App\Http\Controllers\Admin\AdminShopifyStoreController;
use App\Http\Controllers\Admin\AdminStoreReviewController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisteredAccountController;
use App\Http\Controllers\Customer\CustomerAppController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\Customer\ExportController;
use App\Http\Controllers\Customer\FollowAppController;
use App\Http\Controllers\Customer\SavedSearchController;
use App\Http\Controllers\Customer\SettingsController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

Route::get('/register', [RegisteredAccountController::class, 'create'])->name('register');
Route::post('/register', [RegisteredAccountController::class, 'store'])->name('register.store');

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');

Route::middleware(['auth'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/', CustomerDashboardController::class)->name('dashboard');
    Route::get('/apps', [CustomerAppController::class, 'index'])->name('apps.index');
    Route::get('/apps/{shopifyApp}', [CustomerAppController::class, 'show'])->name('apps.show');
    Route::post('/apps/{shopifyApp}/follow', [FollowAppController::class, 'store'])->name('apps.follow');
    Route::delete('/apps/{shopifyApp}/follow', [FollowAppController::class, 'destroy'])->name('apps.unfollow');
    Route::resource('saved-searches', SavedSearchController::class)->except(['show', 'create', 'edit']);
    Route::post('/export/apps', [ExportController::class, 'apps'])->name('export.apps');
    Route::post('/export/reviews/{shopifyApp}', [ExportController::class, 'reviews'])->name('export.reviews');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

Route::middleware(['auth', \App\Http\Middleware\EnsureSuperAdmin::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::resource('accounts', AdminAccountController::class);
        Route::resource('users', AdminUserController::class);
        Route::resource('plans', AdminPlanController::class);
        Route::get('/shopify-apps', [AdminShopifyAppController::class, 'index'])->name('shopify-apps.index');
        Route::get('/shopify-apps/{shopifyApp}', [AdminShopifyAppController::class, 'show'])->name('shopify-apps.show');
        Route::post('/shopify-apps/{shopifyApp}/unlist', [AdminShopifyAppController::class, 'unlist'])->name('shopify-apps.unlist');
        Route::post('/shopify-apps/{shopifyApp}/relist', [AdminShopifyAppController::class, 'relist'])->name('shopify-apps.relist');
        Route::get('/shopify-stores', [AdminShopifyStoreController::class, 'index'])->name('shopify-stores.index');
        Route::post('/shopify-stores/{shopifyStore}/unlist', [AdminShopifyStoreController::class, 'unlist'])->name('shopify-stores.unlist');
        Route::post('/shopify-stores/{shopifyStore}/relist', [AdminShopifyStoreController::class, 'relist'])->name('shopify-stores.relist');
        Route::get('/store-reviews', [AdminStoreReviewController::class, 'index'])->name('store-reviews.index');
        Route::get('/pain-points', AdminPainPointsController::class)->name('pain-points');
    });
