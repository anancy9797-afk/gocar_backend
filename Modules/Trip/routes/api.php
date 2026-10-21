<?php

use Illuminate\Support\Facades\Route;
use Modules\Trip\Http\Controllers\TripController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
  Route::get('admin/trips', [TripController::class, 'getAllTrips'])->name('admin.trip.index');
    Route::apiResource('trips', TripController::class)->names('trip');
    Route::get('/driver/notifications/trips', [
    TripController::class,
    'pendingTrips'
]);
Route::middleware('auth:sanctum')->get(
    'driver/trips',
    [TripController::class, 'allTrips']
);

    Route::post(
        'driver/trips/{id}/accept',
        [TripController::class, 'acceptTrip']
    );
});
