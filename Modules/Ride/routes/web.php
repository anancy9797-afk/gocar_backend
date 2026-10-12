<?php

use Illuminate\Support\Facades\Route;
use Modules\Ride\Http\Controllers\RideController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('rides', RideController::class)->names('ride');
});
