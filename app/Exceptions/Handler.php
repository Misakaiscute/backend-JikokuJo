<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\QueryException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            if ($exception instanceof QueryException) {
                return response()->json([
                    'data'   => [],
                    'errors' => [['error' => 'Database error: ' . $exception->getMessage()]]
                ], 500);
            }

            if ($exception instanceof \Exception || $exception instanceof \Error) {
                return response()->json([
                    'data'   => [],
                    'errors' => [['error' => 'Server error: ' . $exception->getMessage()]]
                ], 500);
            }
        }

        return parent::render($request, $exception);
    }
}