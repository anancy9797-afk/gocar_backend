<?php

use Illuminate\Support\Facades\Route;
use Modules\Rider\Http\Controllers\RiderController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('riders', RiderController::class)->names('rider');
});
