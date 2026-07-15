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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'agent' => \App\Http\Middleware\AuthenticateAgent::class,
            'worklive.admin' => \App\Http\Middleware\AuthenticateWorkLiveAdmin::class,
            'worklive.web' => \App\Http\Middleware\EnsureWorkLiveAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // El Tracker Windows no siempre envía Accept: application/json. Para
        // conservar su contrato, cualquier fallo de /api debe responder JSON
        // en vez de la página HTML de excepción de Laravel.
        $exceptions->shouldRenderJsonWhen(fn (\Illuminate\Http\Request $request, \Throwable $exception) => $request->is('api/*') || $request->expectsJson());
    })->create();
