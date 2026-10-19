<?php

use Illuminate\Support\Facades\Route;
use Modules\Driver\Http\Controllers\DriverController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('drivers', DriverController::class)->names('driver');
});

Route::middleware('auth:admin')->prefix('v1/admin')->group(function () {
    Route::get('/drivers/{id}', [DriverController::class, 'show']);
});



Route::patch('/v1/admin/drivers/{id}/approve', [
    DriverController::class,
    'approve',
]);

Route::patch('/v1/admin/drivers/{id}/reject', [
    DriverController::class,
    'reject',
]);

Route::prefix('v1/admin')
    ->middleware('auth:admin')
    ->group(function () {
        Route::get(
            '/notifications/inactive-drivers',
            [DriverController::class, 'inactiveDrivers']
        );
    });
