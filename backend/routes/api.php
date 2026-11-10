<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\KycController;
use App\Http\Controllers\Api\V1\AdminWalletController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\PasswordController;
use App\Http\Controllers\Api\V1\VerificationController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Api\V1\WalletFinancialController;
use App\Http\Controllers\Api\V1\AdminLedgerController;
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
        Route::post('/wallets', [WalletController::class, 'store']);
        Route::get('/wallets/{wallet}', [WalletController::class, 'show']);
        Route::patch('/wallets/{wallet}/preferences', [WalletController::class, 'updatePreferences']);
        Route::get('/wallets/{wallet}/currencies', [WalletController::class, 'currencies']);
        Route::post('/wallets/{wallet}/currencies', [WalletController::class, 'addCurrency']);
        Route::get('/wallets/{wallet}/currencies/{code}', [WalletController::class, 'currency']);
        Route::get('/currencies', [WalletController::class, 'currenciesCatalog']);
        Route::get('/dashboard/wallet-overview', [WalletController::class, 'overview']);
        Route::get('/wallets/{wallet}/balance', [WalletFinancialController::class, 'balance']);
        Route::get('/wallets/{wallet}/ledger-activity', [WalletFinancialController::class, 'activity']);
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::get('/auth/mfa', [AuthController::class, 'mfaStatus']);
        Route::post('/auth/mfa/enroll', [AuthController::class, 'mfaEnroll']);
        Route::post('/auth/mfa/verify', [AuthController::class, 'mfaVerify']);
        Route::post('/auth/mfa/recovery', [AuthController::class, 'mfaRecovery']);
        Route::get('/kyc', [KycController::class, 'show']);
        Route::post('/kyc/documents', [KycController::class, 'submit']);
        Route::middleware('role:platform_super_admin,compliance_admin,support_agent')->prefix('admin')->group(function (): void {
            Route::get('/wallets', [AdminWalletController::class, 'index']);
            Route::get('/wallets/{wallet}', [AdminWalletController::class, 'show']);
            Route::get('/ledger/accounts', [AdminLedgerController::class, 'accounts']);
            Route::get('/ledger/transactions', [AdminLedgerController::class, 'transactions']);
            Route::get('/ledger/transactions/{transaction}', [AdminLedgerController::class, 'transaction']);
            Route::get('/ledger/reconciliation', [AdminLedgerController::class, 'reconciliation']);
            Route::get('/ledger/audit-events', [AdminLedgerController::class, 'auditEvents']);
        });
    });
});
