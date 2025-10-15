<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth
    Route::post('/auth/register', [\App\Http\Controllers\API\V1\AuthController::class, 'register']);
    Route::post('/auth/login', [\App\Http\Controllers\API\V1\AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Contracts
        Route::get('/contracts', [\App\Http\Controllers\API\V1\ContractController::class, 'index']);
        Route::get('/contracts/{id}', [\App\Http\Controllers\API\V1\ContractController::class, 'show']);
        Route::patch('/contracts/{id}', [\App\Http\Controllers\API\V1\ContractController::class, 'update']);
        Route::post('/contracts', [\App\Http\Controllers\API\V1\ContractController::class, 'store']);
        Route::patch('/contracts/{id}/sign', [\App\Http\Controllers\API\V1\ContractController::class, 'sign']);
        Route::patch('/contracts/{id}/finalize', [\App\Http\Controllers\API\V1\ContractController::class, 'finalize'])->middleware('role:Admin');
        Route::patch('/contracts/{id}/repair', [\App\Http\Controllers\API\V1\ContractController::class, 'repair'])->middleware('role:Admin');

        // Verification
        Route::get('/verifications', [\App\Http\Controllers\API\V1\VerificationController::class, 'index'])->middleware('role:Admin');
        Route::patch('/verifications/{id}/review', [\App\Http\Controllers\API\V1\VerificationController::class, 'review'])->middleware('role:Admin');
        Route::post('/users/{id}/verify', [\App\Http\Controllers\API\V1\VerificationController::class, 'submit']);

        // Transactions removed: platform is contracts-first; no payment processing

        // Notifications
        Route::get('/notifications', [\App\Http\Controllers\API\V1\NotificationController::class, 'index']);
        Route::patch('/notifications/{id}/read', [\App\Http\Controllers\API\V1\NotificationController::class, 'markRead']);
        Route::patch('/notifications/read-all', [\App\Http\Controllers\API\V1\NotificationController::class, 'markAllRead']);
        Route::get('/notifications/unread-count', [\App\Http\Controllers\API\V1\NotificationController::class, 'unreadCount']);
    });

    // Webhooks removed: no external payment providers
});