<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\SejoliWebhookController;

Route::prefix('license')->group(function () {
    Route::get('/status', [LicenseController::class, 'status'])->name('api.license.status');
    Route::post('/force-revoke', [LicenseController::class, 'forceRevoke'])->name('api.license.force-revoke');
});

Route::post('/sejoli/webhook', [SejoliWebhookController::class, 'handle'])->name('api.sejoli.webhook');

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'app_name' => config('app.name'),
    ]);
});
