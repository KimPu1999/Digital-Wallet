<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\KycController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\PasswordController;
use App\Http\Controllers\Api\V1\VerificationController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthController::class);
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:auth');
    Route::post('/auth/merchant/register', [AuthController::class, 'merchantRegister'])->middleware('throttle:auth');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::post('/auth/admin/login', [AuthController::class, 'adminLogin'])->middleware('throttle:auth');
    Route::post('/auth/password/forgot', [PasswordController::class, 'forgot'])->middleware('throttle:auth');
    Route::post('/auth/password/reset', [PasswordController::class, 'reset'])->middleware('throttle:auth');
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/email/resend', [VerificationController::class, 'resend'])->middleware('throttle:auth');
        Route::get('/auth/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
        Route::get('/wallets', [WalletController::class, 'index']);
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::get('/auth/mfa', [AuthController::class, 'mfaStatus']);
        Route::post('/auth/mfa/enroll', [AuthController::class, 'mfaEnroll']);
        Route::post('/auth/mfa/verify', [AuthController::class, 'mfaVerify']);
        Route::post('/auth/mfa/recovery', [AuthController::class, 'mfaRecovery']);
        Route::get('/kyc', [KycController::class, 'show']);
        Route::post('/kyc/documents', [KycController::class, 'submit']);
    });
});
