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

    // Dashboard (accessible by admin and validator)
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->middleware('role:admin,validator');

    // Units (managed by admin)
    Route::apiResource('units', UnitController::class)->middleware('role:admin');
    Route::get('/units/{unit}/qr', [UnitController::class, 'getQRCode'])->middleware('role:admin');
    Route::post('/units/{unit}/regenerate-qr', [UnitController::class, 'regenerateQRCode'])->middleware('role:admin');

    // Instruments (managed by admin)
    Route::apiResource('instruments', InstrumentController::class)->middleware('role:admin');

    // Transactions
    Route::prefix('transactions')->group(function () {
        // All authenticated users can view transactions.
        Route::get('/', [TransactionController::class, 'index']);
        Route::get('/{transaction}', [TransactionController::class, 'show']);

        // Scanning and creating transactions are restricted to operators.
        Route::post('/scan-unit', [TransactionController::class, 'scanUnit'])->middleware('throttle:scan', 'role:operator');
        Route::post('/create-steril-distribution', [TransactionController::class, 'createSterilDistribution'])->middleware('throttle:transaction', 'role:operator');
        Route::post('/create-kotor-pickup', [TransactionController::class, 'createKotorPickup'])->middleware('throttle:transaction', 'role:operator');
        Route::post('/create-cssd-return', [TransactionController::class, 'createCssdReturn'])->middleware('throttle:transaction', 'role:operator');

        // Validation is restricted to validators.
        Route::post('/validate', [TransactionController::class, 'validateTransaction'])->middleware('throttle:transaction', 'role:validator');

        // All roles can cancel, but logic is handled in the service/controller.
        Route::post('/{transaction}/cancel', [TransactionController::class, 'cancel'])->middleware('throttle:transaction');

        // Transaction Photos (restricted to operators)
        Route::post('/{transaction}/photos', [TransactionPhotoController::class, 'upload'])->middleware('role:operator');
        Route::delete('/{transaction}/photos/{photo}', [TransactionPhotoController::class, 'destroy'])->middleware('role:operator');
        Route::get('/{transaction}/photos', [TransactionPhotoController::class, 'index']);
    });

    // Reports (admin and validator only)
    Route::get('/report/export-excel', [ReportController::class, 'exportExcel'])->middleware('role:admin,validator');

    // Activity Logs (admin and validator only)
    Route::apiResource('activity-logs', ActivityLogController::class)->only(['index', 'show'])->middleware('role:admin,validator');

    // QR parsing can be done by any authenticated user.
    Route::post('/qr/parse', [QRController::class, 'parse']);
});
