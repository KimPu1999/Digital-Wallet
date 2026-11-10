<?php

return ['default' => env('LOG_CHANNEL', 'stack'), 'deprecations' => ['trace' => false, 'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null')], 'channels' => ['stack' => ['driver' => 'stack', 'channels' => ['single'], 'ignore_exceptions' => false], 'single' => ['driver' => 'single', 'path' => storage_path('logs/laravel.log'), 'level' => env('LOG_LEVEL', 'warning'), 'replace_placeholders' => true], 'null' => ['driver' => 'monolog', 'handler' => Monolog\Handler\NullHandler::class]]];
