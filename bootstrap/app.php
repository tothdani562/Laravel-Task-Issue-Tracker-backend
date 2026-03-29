<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $toErrorResponse = static function (Request $request, int $statusCode, string $message) {
            return response()->json([
                'success' => false,
                'statusCode' => $statusCode,
                'message' => $message,
                'path' => '/'.$request->path(),
                'timestamp' => now()->toISOString(),
            ], $statusCode);
        };

        $exceptions->render(function (ValidationException $exception, Request $request) use ($toErrorResponse) {
            if (! $request->is('api/*')) {
                return null;
            }

            $firstError = $exception->validator->errors()->first() ?: 'Validation failed.';

            return $toErrorResponse($request, 422, $firstError);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($toErrorResponse) {
            if (! $request->is('api/*')) {
                return null;
            }

            return $toErrorResponse($request, 401, $exception->getMessage() ?: 'Unauthenticated.');
        });

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) use ($toErrorResponse) {
            if (! $request->is('api/*')) {
                return null;
            }

            return $toErrorResponse($request, 429, 'Too many requests.');
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($toErrorResponse) {
            if (! $request->is('api/*')) {
                return null;
            }

            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage() ?: 'Request failed.';

            return $toErrorResponse($request, $statusCode, $message);
        });

        $exceptions->render(function (Throwable $exception, Request $request) use ($toErrorResponse) {
            if (! $request->is('api/*')) {
                return null;
            }

            return $toErrorResponse($request, 500, 'Internal server error.');
        });
    })->create();
