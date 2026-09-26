<?php

use App\Http\Controllers\AccountingReportController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\SaverController;
use App\Http\Controllers\Admin\SystemConfigController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\Mobile\AuthController as MobileAuthController;
use App\Http\Controllers\Mobile\DashboardController as MobileDashboardController;
use App\Http\Controllers\Mobile\MutasiController as MobileMutasiController;
use App\Http\Controllers\MonthlyReportController;
use App\Http\Controllers\Nasabah\DashboardController as NasabahDashboardController;
use App\Http\Controllers\Nasabah\MutasiController;
use App\Http\Controllers\Supervisor\ReportController;
use App\Http\Controllers\Teller\ClosingController;
use App\Http\Controllers\Teller\MemberActivationController;
use App\Http\Controllers\Teller\MemberRegistrationController;
use App\Http\Controllers\Teller\MemberSearchController;
use App\Http\Controllers\Teller\PassbookController;
use App\Http\Controllers\Teller\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login')->name('api.login');
Route::post('/logout', [LoginController::class, 'logout'])->name('api.logout');

Route::post('/forgot-password/request-otp', [ForgotPasswordController::class, 'requestOtp'])->middleware('throttle:forgot-password')->name('api.password.otp.request');
Route::post('/forgot-password/reset', [ForgotPasswordController::class, 'resetCredential'])->middleware('throttle:forgot-password')->name('api.password.reset.submit');

Route::prefix('mobile')->group(function () {
    Route::post('/login', [MobileAuthController::class, 'login'])->middleware('throttle:login')->name('api.mobile.login');
    Route::post('/forgot-pin/request-otp', [ForgotPasswordController::class, 'requestOtp'])->middleware('throttle:forgot-password')->name('api.mobile.forgot-pin.request');
    Route::post('/forgot-pin/reset', [ForgotPasswordController::class, 'resetCredential'])->middleware('throttle:forgot-password')->name('api.mobile.forgot-pin.reset');

    Route::middleware('mobile.auth')->group(function () {
        Route::post('/logout', [MobileAuthController::class, 'logout'])->name('api.mobile.logout');
        Route::get('/me', [MobileAuthController::class, 'me'])->name('api.mobile.me');
        Route::get('/dashboard', [MobileDashboardController::class, 'index'])->name('api.mobile.dashboard');
        Route::get('/card', [MobileDashboardController::class, 'card'])->name('api.mobile.card');
        Route::get('/mutasi', [MobileMutasiController::class, 'index'])->name('api.mobile.mutasi');
        Route::get('/statement', [MobileMutasiController::class, 'statement'])->name('api.mobile.statement');
    });
});

Route::middleware(['role:admin'])->prefix('admin')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);

    Route::get('/members', [MemberController::class, 'index']);
    Route::post('/members', [MemberController::class, 'store']);
    Route::post('/members/import', [MemberController::class, 'import']);
    Route::match(['put', 'patch'], '/members/{member}', [MemberController::class, 'update']);
    Route::delete('/members/{member}', [MemberController::class, 'destroy']);

    Route::get('/savers', [SaverController::class, 'index']);
    Route::match(['put', 'patch'], '/savers/{saver}', [SaverController::class, 'update']);
    Route::patch('/savers/{saver}/toggle-status', [SaverController::class, 'toggleStatus']);
    Route::delete('/savers/{saver}', [SaverController::class, 'destroy']);

    Route::get('/accounts/{account}', [AccountController::class, 'show']);

    Route::get('/config', [SystemConfigController::class, 'edit']);
    Route::put('/config', [SystemConfigController::class, 'update']);

    Route::get('/journals', [JournalController::class, 'index']);
    Route::get('/reports/monthly', [MonthlyReportController::class, 'index']);
    Route::get('/reports/general-ledger', [AccountingReportController::class, 'generalLedger']);
    Route::get('/reports/trial-balance', [AccountingReportController::class, 'trialBalance']);
    Route::get('/reports/export/{type?}', [AccountingReportController::class, 'exportCsv']);
});

Route::middleware(['role:teller'])->prefix('teller')->group(function () {
    Route::get('/members/search', [MemberSearchController::class, 'search']);
    Route::get('/members/lookup', [MemberSearchController::class, 'lookup']);
    Route::post('/members/register', [MemberRegistrationController::class, 'store']);
    Route::post('/members/{member}/activate', [MemberActivationController::class, 'store']);
    Route::get('/members/{account}', [MemberSearchController::class, 'show']);

    Route::post('/deposits/{account}', [TransactionController::class, 'storeDeposit']);
    Route::post('/withdrawals/{account}', [TransactionController::class, 'storeWithdrawal']);
    Route::post('/transfers/{account}', [TransactionController::class, 'storeTransfer']);
    Route::get('/passbook/{account}', [PassbookController::class, 'show']);
    Route::get('/passbook/{account}/print', [PassbookController::class, 'print']);
    Route::get('/transactions/{transaction}/receipt', [TransactionController::class, 'receipt']);

    Route::get('/closing', [ClosingController::class, 'index']);
    Route::post('/closing', [ClosingController::class, 'store']);
    Route::get('/closing/reports/{report}/print', [ClosingController::class, 'print']);
});

Route::middleware(['role:supervisor'])->prefix('supervisor')->group(function () {
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/{report}', [ReportController::class, 'show']);
    Route::post('/reports/{report}/approve', [ReportController::class, 'approve']);
    Route::post('/reports/{report}/reject', [ReportController::class, 'reject']);
    Route::get('/journals', [JournalController::class, 'index']);
    Route::get('/reports-monthly', [MonthlyReportController::class, 'index']);
    Route::get('/reports-accounting/general-ledger', [AccountingReportController::class, 'generalLedger']);
    Route::get('/reports-accounting/trial-balance', [AccountingReportController::class, 'trialBalance']);
    Route::get('/reports-accounting/export/{type?}', [AccountingReportController::class, 'exportCsv']);
});

Route::middleware(['role:nasabah'])->prefix('nasabah')->group(function () {
    Route::get('/dashboard', [NasabahDashboardController::class, 'index']);
    Route::get('/mutasi', [MutasiController::class, 'index']);
    Route::get('/statement', [MutasiController::class, 'statement']);
});
