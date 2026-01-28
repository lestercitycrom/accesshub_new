<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'no-cache' => \App\Http\Middleware\NoCache::class,
            'log-webapp' => \App\Http\Middleware\LogWebAppRequests::class,
        ]);
        
        // Exclude WebApp API routes from CSRF verification
        // They use session-based auth via Telegram initData, not CSRF tokens
        $middleware->validateCsrfTokens(except: [
            'webapp/api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (str_starts_with($request->path(), 'webapp/api/issue')) {
                \Illuminate\Support\Facades\Log::error('Exception in WebApp issue route', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'input' => $request->all(),
                ]);
            }
        });
    })->create();
