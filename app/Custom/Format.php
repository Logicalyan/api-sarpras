<?php

namespace App\Custom;

class Format
{
    public static function apiResponse(int $code = 200, string $message = "", mixed $data = null, mixed $error = null)
    {
        // Hanya cek rentang kode status untuk menentukan success
        $success = $code >= 200 && $code < 300;

        // Validasi kode status HTTP (opsional)
        if ($code < 100 || $code > 599) {
            $code = 500; // Default ke 500 jika kode tidak valid
            $message = "Invalid HTTP status code";
            $success = false;
        }

        return response()->json([
            "success" => $success,
            "message" => $message,
            "data" => $data,
            "error" => $error
        ], $code);
    }
}
