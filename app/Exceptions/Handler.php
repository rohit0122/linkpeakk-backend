<?php

namespace App\Exceptions;

use App\Helpers\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        // Handle API requests with standardized responses
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions with standardized response format
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return JsonResponse
     */
    protected function handleApiException($request, Throwable $e): JsonResponse
    {
        // Validation Exception
        if ($e instanceof ValidationException) {
            return ApiResponse::validationError(
                $e->errors(),
                'Validation failed'
            );
        }

        // Authentication Exception
        if ($e instanceof AuthenticationException) {
            return ApiResponse::unauthorized(
                $e->getMessage() ?: 'Unauthenticated'
            );
        }

        // Model Not Found Exception
        if ($e instanceof ModelNotFoundException) {
            return ApiResponse::notFound(
                'Resource not found'
            );
        }

        // Not Found HTTP Exception
        if ($e instanceof NotFoundHttpException) {
            return ApiResponse::notFound(
                'Endpoint not found'
            );
        }

        // Method Not Allowed Exception
        if ($e instanceof MethodNotAllowedHttpException) {
            return ApiResponse::error(
                'Method not allowed',
                [],
                405
            );
        }

        // Access Denied Exception
        if ($e instanceof AccessDeniedHttpException) {
            return ApiResponse::forbidden(
                $e->getMessage() ?: 'Access forbidden'
            );
        }

        // Generic Exception
        return $this->handleGenericException($e);
    }

    /**
     * Handle generic exceptions
     *
     * @param  \Throwable  $e
     * @return JsonResponse
     */
    protected function handleGenericException(Throwable $e): JsonResponse
    {
        // In production, hide sensitive error details
        if (config('app.debug')) {
            return ApiResponse::error(
                $e->getMessage() ?: 'An error occurred',
                [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ],
                500
            );
        }

        // Production error response
        return ApiResponse::error(
            'An error occurred while processing your request',
            [],
            500
        );
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::unauthorized('Unauthenticated');
        }

        return redirect()->guest(route('login'));
    }
}
