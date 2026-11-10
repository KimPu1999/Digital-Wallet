<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([__DIR__.'/../app/Console/Commands'])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->appendToGroup('api', [Illuminate\Session\Middleware\StartSession::class, App\Http\Middleware\RequestCorrelationId::class]);
        $middleware->alias(['role' => App\Http\Middleware\RoleMiddleware::class, 'platform.admin' => App\Http\Middleware\PlatformAdminMiddleware::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API exception formatting can be extended here as the platform grows.
    })->create();
