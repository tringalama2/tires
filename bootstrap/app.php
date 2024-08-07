<?php

use App\Http\Middleware\ActiveVehicleTiresMiddleware;
use App\Http\Middleware\FirstVehicleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware
//            ->web(append: [
//                MyGlobalWebMiddleware::class,
//            ])
            ->alias([
                'firstVehicleExists' => FirstVehicleMiddleware::class,
                'activeVehicleTires' => ActiveVehicleTiresMiddleware::class,
            ]);
        //$middleware->append(MyGlobalMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
