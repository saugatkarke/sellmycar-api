<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'timestamp' => now(),
            'data' => $data,
        ], $status);
    }

    public static function error(
        string $message = 'Something went wrong',
        mixed $errors = null,
        int $status = 400
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'timestamp' => now(),
            'errors' => $errors,
        ], $status);
    }
}
