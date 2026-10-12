<?php

use Illuminate\Support\Facades\Route;
use Modules\Driver\Http\Controllers\DriverController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('drivers', DriverController::class)->names('driver');
});
