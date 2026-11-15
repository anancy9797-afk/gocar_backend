<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\WalletController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('wallets', WalletController::class)->names('wallet');
});

Route::get(
    'admin/drivers/{driverId}/wallet',
    [WalletController::class, 'driverWallet']
);