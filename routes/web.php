<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StyleguideController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('auth.register.create');
    Route::post('/register', [RegisterController::class, 'store'])->name('auth.register.store');
});

if (app()->isLocal()) {
    Route::get('/styleguide', StyleguideController::class)->name('styleguide');
}
