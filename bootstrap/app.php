<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Broadcast;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Broadcast::routes(['middleware' => ['auth:sanctum']]);
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\App\Http\Middleware\CorsMiddleware::class);
    })->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
