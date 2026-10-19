<?php

use Illuminate\Support\Facades\Route;
use Modules\Trip\Http\Controllers\TripController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('trips', TripController::class)->names('trip');
});
