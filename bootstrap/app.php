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
        // Validation Exception
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            $errors = $e->errors();
            // Get the first error message to show at the top level
            $message = collect($errors)->flatten()->first() ?: 'Validation failed.';

            return response()->json([
                'success' => false,
                'message' => $message,
                'data' => [
                    'errors' => $errors,
                ],
            ], 422);
        });

        // Not Found Exception
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint or resource not found.',
                'data' => [],
            ], 404);
        });

        // Catch-all Generic Exception
        $exceptions->render(function (\Throwable $e, $request) {
            if (config('app.debug')) {
                return null; // Let Laravel show the detailed error page/json
            }

            $message = $e->getMessage();
            $code = 500;

            // If it's a standard HTTP exception, use its status code
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                $code = $e->getStatusCode();
            }

            // Sanitization: Don't leak raw SQL or system errors in production
            if (empty($message) || str_contains($message, 'SQL') || str_contains($message, 'PDOException')) {
                $message = 'An unexpected error occurred while processing your request.';
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'data' => [],
            ], $code);
        });
    })->create();
