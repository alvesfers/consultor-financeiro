<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// 🚨 importe seu middleware:
use App\Http\Middleware\EnsureUserHasRole;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias para usar nas rotas: 'role'
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // (opcional) adicionar a grupos padrão:
        // $middleware->appendToGroup('web', EnsureUserHasRole::class);
        // $middleware->appendToGroup('api', EnsureUserHasRole::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
