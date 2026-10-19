<?php

use Illuminate\Support\Facades\Route;
use Modules\Ride\Http\Controllers\RideController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/rides', [RideController::class, 'index']);
});