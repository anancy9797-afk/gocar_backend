<?php

use Illuminate\Support\Facades\Route;
use Modules\Ride\Http\Controllers\RideController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('rides', RideController::class)->names('ride');
});
