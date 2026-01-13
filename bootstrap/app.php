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
    ->withMiddleware(function (Middleware $middleware) {
        // Add API middleware
        $middleware->api(prepend: [
            \App\Http\Middleware\ApiSecurityMiddleware::class,
        ]);
        
        // Add rate limiting to API routes
        $middleware->alias([
            'api.rate.limit' => \App\Http\Middleware\ApiRateLimitMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint or resource not found.',
                'data' => []
            ], 200);
        });

        $exceptions->render(function (\Throwable $e, $request) {
            if (config('app.debug')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'An internal error occurred.',
                'data' => []
            ], 200);
        });
    })->create();
