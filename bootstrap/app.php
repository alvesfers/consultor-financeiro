<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// ✅ importa seus middlewares personalizados
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\VerifyConsultantScope;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias usados nas rotas
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'consultant.scope' => VerifyConsultantScope::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Aqui você pode configurar handlers personalizados de exceções, se quiser.
    })
    ->create();
