<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Exceptions\OutOfStockException;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (
            OutOfStockException $e,
            Request $request
        ) {

            if ($request->expectsJson()) {

                return ApiResponse::error(
                    $e->getMessage(),
                    null,
                    409
                );
            }
        });
    })->create();
