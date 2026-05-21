<?php

use App\Http\Controllers\Auth\RegisteredAccountController;
use App\Models\Plan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $plans = Plan::query()
        ->public()
        ->active()
        ->with(['features' => fn ($q) => $q->orderBy('feature_key')])
        ->orderBy('sort_order')
        ->get();

    return view('landing', ['plans' => $plans]);
})->name('home');

Route::get('/register', [RegisteredAccountController::class, 'create'])->name('register');
Route::post('/register', [RegisteredAccountController::class, 'store'])->name('register.store');
