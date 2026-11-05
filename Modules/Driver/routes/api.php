<?php

use Illuminate\Support\Facades\Route;
use Modules\Driver\Http\Controllers\DriverController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
      Route::post('/drivers/availability', [
        DriverController::class,
        'toggleAvailability'
    ]);
    Route::apiResource('drivers', DriverController::class)->names('driver');
    Route::middleware(['throttle:10,1'])->post('/update-location', [DriverController::class, 'updateLocation']);
    Route::post('/driver/trips/{id}/arrived', [DriverController::class, 'arrivedAtPickup']);
    Route::post('/driver/trips/{id}/start', [DriverController::class, 'startTrip']);
    Route::post('/driver/trips/{id}/end', [DriverController::class, 'endTrip']);
    Route::post('/driver/trips/{id}/paid', [DriverController::class, 'markTripAsPaid']);
    Route::post('/driver/my-wallet', [DriverController::class, 'getMyWallet']);
});

Route::middleware('auth:admin')->prefix('v1/admin')->group(function () {
    Route::get('/drivers/{id}', [DriverController::class, 'show']);
});



Route::post('/v1/admin/drivers/{id}/approve', [
    DriverController::class,
    'approve',
]);

Route::post('/v1/admin/drivers/{id}/reject', [
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
