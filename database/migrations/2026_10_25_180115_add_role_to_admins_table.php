<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Modules\Driver\Http\Controllers\DriverController;

// مسارات السائقين العاديين
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('drivers', DriverController::class)->names('driver');
});

// مسارات الأدمن (تأكد أن جميعها داخل الـ group الخاص بـ auth:admin)
Route::middleware('auth:admin')->prefix('v1/admin')->group(function () {
    Route::get('/drivers/{id}', [DriverController::class, 'show']);
    Route::get('/notifications/inactive-drivers', [DriverController::class, 'inactiveDrivers']);
    
    // نقلنا مسارات الapprove و reject إلى هنا لتكون محمية بـ auth:admin بشكل صحيح
    Route::patch('/drivers/{id}/approve', [DriverController::class, 'approve']);
    Route::patch('/drivers/{id}/reject', [DriverController::class, 'reject']);
});