<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Inertia sends Resources through toResponse(), which would otherwise wrap every page prop in "data".
        JsonResource::withoutWrapping();

        // The rules for choosing a password, used when registering and resetting a password.
        Password::defaults(fn (): Password => Password::min(8)
            ->max(256)
            ->letters()
            // ->mixedCase()
            ->numbers()
            ->symbols());
        // ->uncompromised()

        // 5 attempts a minute for each auth form ("throttle:auth"), counted per route and IP address
        // so that, say, failed logins don't use up the limit for registering.
        RateLimiter::for('auth', fn (Request $request): Limit => Limit::perMinute(5)->by($request->route()?->getName().'|'.$request->ip()));
    }
}
