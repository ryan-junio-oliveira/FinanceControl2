<?php

use App\Http\Middleware\EnsureActivePlan;
use App\Http\Middleware\EnsureFamilyOwnership;
use App\Http\Middleware\EnsureFamilyRole;
use App\Http\Middleware\NoStoreHtml;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'family.role' => EnsureFamilyRole::class,
            'family.ownership' => EnsureFamilyOwnership::class,
            'plan.active' => EnsureActivePlan::class,
        ]);
        $middleware->web(append: [
            NoStoreHtml::class,
            SecurityHeaders::class,
        ]);

        // Webhook do Mercado Pago chega sem token CSRF.
        $middleware->validateCsrfTokens(except: [
            'webhooks/mercadopago',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
