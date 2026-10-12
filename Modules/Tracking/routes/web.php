<?php

use Illuminate\Support\Facades\Route;
use Modules\Tracking\Http\Controllers\TrackingController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('trackings', TrackingController::class)->names('tracking');
});
