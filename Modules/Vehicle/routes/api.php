<?php

use Illuminate\Support\Facades\Route;
use Modules\Vehicle\Http\Controllers\VehicleController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('vehicles', VehicleController::class)->names('vehicle');
    Route::post('/admin/vehicle-types/{id}', [VehicleController::class, 'destroy']);
    Route::post('/admin/vehicle-type/{id}', [VehicleController::class, 'update']);
});
