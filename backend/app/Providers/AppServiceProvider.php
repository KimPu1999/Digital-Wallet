<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        RateLimiter::for('auth', fn ($request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('fx', fn ($request) => Limit::perMinute(20)->by((string) ($request->user()?->id ?? $request->ip())));
        RateLimiter::for('fx-conversion', fn ($request) => Limit::perMinute(10)->by((string) ($request->user()?->id ?? $request->ip())));
    }
}
