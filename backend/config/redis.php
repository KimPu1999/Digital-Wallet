<?php

return [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'options' => ['cluster' => env('REDIS_CLUSTER', 'redis'), 'prefix' => env('REDIS_PREFIX', 'digital_wallet_database_')],
    'default' => ['url' => env('REDIS_URL'), 'host' => env('REDIS_HOST', '127.0.0.1'), 'username' => env('REDIS_USERNAME'), 'password' => env('REDIS_PASSWORD'), 'port' => (int) env('REDIS_PORT', 6379), 'database' => (int) env('REDIS_DB', 0)],
    'cache' => ['url' => env('REDIS_URL'), 'host' => env('REDIS_HOST', '127.0.0.1'), 'username' => env('REDIS_USERNAME'), 'password' => env('REDIS_PASSWORD'), 'port' => (int) env('REDIS_PORT', 6379), 'database' => (int) env('REDIS_CACHE_DB', 1)],
];
