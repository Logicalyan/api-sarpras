<?php

namespace App\Custom;

class Format
{
    public static function apiResponse(int $code = 200, string $message = " ", mixed $data = null, mixed $error = null)
    {
        $success = is_numeric($code) && $code > 199 && $code < 300;
        return response()->json([
            "success" => $success,
            "message" => $message,
            "data" => $data,
            "error" => $error
        ], $code);
    }
}
