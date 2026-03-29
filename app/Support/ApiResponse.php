<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(mixed $data, int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ], $statusCode);
    }

    public static function error(string $message, int $statusCode): JsonResponse
    {
        return response()->json([
            'success' => false,
            'statusCode' => $statusCode,
            'message' => $message,
            'path' => '/'.request()->path(),
            'timestamp' => now()->toISOString(),
        ], $statusCode);
    }

    public static function notImplemented(string $message = 'Not implemented yet.'): JsonResponse
    {
        return self::error($message, 501);
    }
}
