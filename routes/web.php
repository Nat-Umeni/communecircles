<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\CircleController;
use App\Http\Controllers\JoinRequestController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\StyleguideController;
use Illuminate\Support\Facades\Route;

// NOTE: the home page shows the circle list too, until there's a dashboard for it.
Route::get('/', [CircleController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('auth.register.create');
    Route::post('/register', [RegisterController::class, 'store'])->name('auth.register.store')->middleware('throttle:auth');

    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->name('login.store')->middleware('throttle:auth');

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email')->middleware('throttle:auth');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update')->middleware('throttle:auth');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

    Route::get('/verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name('verification.verify')->middleware(['signed', 'throttle:auth']);
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])->name('verification.send')->middleware('throttle:auth');

    Route::resource('circles', CircleController::class)->except(['index', 'show']);
    Route::post('/circles/{circle}/join-requests', [JoinRequestController::class, 'store'])->name('circles.join-requests.store');

    // GeoNames' free account allows 1,000 requests an hour, so each member gets 30 place searches a minute.
    Route::get('/places', PlaceController::class)->name('places.search')->middleware('throttle:30,1');

    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::post('/approvals/{approval}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{approval}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
});

// NOTE: open to guests and members alike, and registered after the group above so /circles/create
// isn't read as a circle called "create".
Route::resource('circles', CircleController::class)->only(['index', 'show']);

if (app()->isLocal()) {
    Route::get('/styleguide', StyleguideController::class)->name('styleguide');
}
