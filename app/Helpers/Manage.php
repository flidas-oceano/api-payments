<?php

namespace App\Helpers;

class Manage
{
    public static function error($e): \Illuminate\Http\JsonResponse
    {
        $err = [
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            // 'trace' => $e->getTraceAsString(),
        ];

        \Log::error("Error: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));

        return response()->json([
            $err
        ], 500);
    }
}