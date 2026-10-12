<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\PhoneAuthController;
use Modules\Auth\Http\Controllers\DriverAuthController;
use Modules\Auth\Http\Controllers\AuthController;

// --- المسارات العامة ---
Route::post('/auth/check-phone', [PhoneAuthController::class, 'checkPhone']);
Route::post('/admin/login', [PhoneAuthController::class, 'adminLogin']);

// مسارات السائق العامة (تسجيل الدخول/التحقق)
Route::post('/driver/check-phone', [DriverAuthController::class, 'checkPhone']);

// --- المسارات المحمية (للعملاء والأدمن) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/verify-otp', [PhoneAuthController::class, 'verifyOtp']);
    Route::post('/auth/complete-profile', [PhoneAuthController::class, 'completeProfile']);
    
    // مسارات الأدمن
    Route::get('/admin/clients', [PhoneAuthController::class, 'getAllClients']);
});

// --- المسارات المحمية (خاصة بالسائقين فقط) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/driver/complete-profile', [DriverAuthController::class, 'completeProfile']);
    Route::get('/driver/profile/me', [DriverAuthController::class, 'me']);
    Route::post('/driver/profile/request-update', [DriverAuthController::class, 'requestUpdateProfile']);
    Route::post('/driver/profile/verify-update', [DriverAuthController::class, 'verifyAndUpdateProfile']);
});

// --- مسارات الملف الشخصي المشتركة (تعديل الحساب وتغيير كلمة المرور) ---
Route::middleware('auth:sanctum')->group(function () {
  Route::get('/profile/me', [AuthController::class, 'me']);
Route::post('/profile/request-update', [AuthController::class, 'requestUpdateProfile']);
    Route::post('/profile/verify-update', [AuthController::class, 'verifyAndUpdateProfile']);
});