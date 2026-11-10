<?php

return [
    'name' => env('APP_NAME', 'Digital Wallet Platform'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
    'timezone' => 'UTC',
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'financial_execution_enabled' => (bool) env('FINANCIAL_EXECUTION_ENABLED', false),
    'faker_locale' => 'en_US',
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [...array_filter(explode(',', (string) env('APP_PREVIOUS_KEYS', '')))],
    'maintenance' => ['driver' => 'file', 'store' => 'database'],
    'providers' => Illuminate\Support\ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
    ])->toArray(),
];
