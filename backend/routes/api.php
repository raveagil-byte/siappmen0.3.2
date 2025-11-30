<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\InstrumentController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionPhotoController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\QRController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/user/device-token', [AuthController::class, 'updateDeviceToken']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Units
    Route::apiResource('units', UnitController::class);
    Route::get('/units/{unit}/qr', [UnitController::class, 'getQRCode']);
    Route::post('/units/{unit}/regenerate-qr', [UnitController::class, 'regenerateQRCode']);

    // Instruments
    Route::apiResource('instruments', InstrumentController::class);

    // Transactions
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::get('/{transaction}', [TransactionController::class, 'show']);
        Route::post('/scan-unit', [TransactionController::class, 'scanUnit'])->middleware('throttle:scan');
        Route::post('/create-steril', [TransactionController::class, 'createSteril'])->middleware('throttle:transaction');
        Route::post('/create-kotor', [TransactionController::class, 'createKotor'])->middleware('throttle:transaction');
        Route::post('/validate', [TransactionController::class, 'validate'])->middleware('throttle:transaction');
        Route::post('/{transaction}/cancel', [TransactionController::class, 'cancel'])->middleware('throttle:transaction');

        // Transaction Photos
        Route::get('/{transaction}/photos', [TransactionPhotoController::class, 'index']);
        Route::post('/{transaction}/photos', [TransactionPhotoController::class, 'upload']);
        Route::delete('/{transaction}/photos/{photo}', [TransactionPhotoController::class, 'destroy']);
    });

    // Reports
    Route::get('/report/export-excel', [ReportController::class, 'exportExcel']);

    // Activity Logs
    Route::apiResource('activity-logs', ActivityLogController::class)->only(['index', 'show']);

    // QR
    Route::post('/qr/parse', [QRController::class, 'parse']);
    Route::get('/qr/generate', [QRController::class, 'generate']);
});
