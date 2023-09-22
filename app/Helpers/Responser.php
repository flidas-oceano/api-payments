<?php

namespace App\Helpers;

class Responser
{
    public static function error($exception): \Illuminate\Http\JsonResponse
    {
        $message = $exception->getMessage();
        $decoded = json_decode($message);
        if (json_last_error() === JSON_ERROR_NONE) {
            $message = $decoded;
        }
        return response()->json([
            "error" => $message
        ], 400);
    }

    public static function success($data, $code = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            "data" => $data
        ], $code);
    }
}
