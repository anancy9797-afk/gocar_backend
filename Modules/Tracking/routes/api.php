<?php

use Illuminate\Support\Facades\Route;
use Modules\Tracking\Http\Controllers\TrackingController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('trackings', TrackingController::class)->names('tracking');
});
