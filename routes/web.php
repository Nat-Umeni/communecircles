<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\StyleguideController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

if (app()->isLocal()) {
    Route::get('/styleguide', StyleguideController::class)->name('styleguide');
}
