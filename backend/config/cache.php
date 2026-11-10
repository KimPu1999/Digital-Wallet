<?php

return ['default' => env('CACHE_STORE', 'database'), 'stores' => ['database' => ['driver' => 'database', 'table' => env('DB_CACHE_TABLE', 'cache'), 'connection' => null, 'lock_connection' => null], 'redis' => ['driver' => 'redis', 'connection' => env('REDIS_CACHE_CONNECTION', 'cache'), 'lock_connection' => env('REDIS_CACHE_CONNECTION', 'cache')]], 'prefix' => env('CACHE_PREFIX', 'digital_wallet_cache')];
