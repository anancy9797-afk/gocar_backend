<?php

use Illuminate\Support\Facades\Route;
use Modules\Rider\Http\Controllers\RiderController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('riders', RiderController::class)->names('rider');
});
