<?php

namespace App\Traits;

trait ApiResponse
{
    public function successResponse($status_code, $message, $data=null)
    {
        return response()->json([
            'status_code' => $status_code,
            'message' => $message,
            'data' => $data,
            'error' => null,
        ] , $status_code);
    }

    public function errorResponse($status_code, $message, $error=null)
    {
        return response()->json([
            'status_code' => $status_code,
            'message' => $message,
            'data' => null,
            'error' => $error,
        ] , $status_code);
    }
}
