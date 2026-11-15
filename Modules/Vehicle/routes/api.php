<?php

use Illuminate\Support\Facades\Route;
use Modules\Vehicle\Http\Controllers\VehicleController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('vehicles', VehicleController::class)->names('vehicle');



});


Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('/admin/vehicle-types/{id}', [VehicleController::class, 'show']);
    Route::put('/admin/vehicle-types/{id}', [VehicleController::class, 'update']);
    Route::delete('/admin/vehicle-types/{id}', [VehicleController::class, 'destroy']);
});
