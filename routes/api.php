<?php

use App\Http\Controllers\AccountingReportController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\SystemConfigController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\Nasabah\DashboardController as NasabahDashboardController;
use App\Http\Controllers\Nasabah\MutasiController;
use App\Http\Controllers\Supervisor\ReportController;
use App\Http\Controllers\Teller\ClosingController;
use App\Http\Controllers\Teller\CustomerSearchController;
use App\Http\Controllers\Teller\PassbookController;
use App\Http\Controllers\Teller\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login')->name('api.login');
Route::post('/logout', [LoginController::class, 'logout'])->name('api.logout');

Route::post('/forgot-password/staff', [ForgotPasswordController::class, 'resetStaffPassword'])->middleware('throttle:forgot-password')->name('api.password.staff.reset');
Route::post('/forgot-password/nasabah', [ForgotPasswordController::class, 'resetNasabahPin'])->middleware('throttle:forgot-password')->name('api.password.nasabah.reset');

Route::middleware(['role:admin'])->prefix('admin')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);

    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::post('/customers/import', [CustomerController::class, 'import']);
    Route::match(['put', 'patch'], '/customers/{customer}', [CustomerController::class, 'update']);
    Route::patch('/customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus']);

    Route::get('/accounts/{account}', [AccountController::class, 'show']);

    Route::get('/config', [SystemConfigController::class, 'edit']);
    Route::put('/config', [SystemConfigController::class, 'update']);

    Route::get('/journals', [JournalController::class, 'index']);
    Route::get('/reports/general-ledger', [AccountingReportController::class, 'generalLedger']);
    Route::get('/reports/trial-balance', [AccountingReportController::class, 'trialBalance']);
    Route::get('/reports/export/{type?}', [AccountingReportController::class, 'exportCsv']);
});

Route::middleware(['role:teller'])->prefix('teller')->group(function () {
    Route::get('/customers/search', [CustomerSearchController::class, 'search']);
    Route::get('/customers/lookup', [CustomerSearchController::class, 'lookup']);
    Route::get('/customers/{account}', [CustomerSearchController::class, 'show']);

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
    Route::get('/reports-accounting/general-ledger', [AccountingReportController::class, 'generalLedger']);
    Route::get('/reports-accounting/trial-balance', [AccountingReportController::class, 'trialBalance']);
    Route::get('/reports-accounting/export/{type?}', [AccountingReportController::class, 'exportCsv']);
});

Route::middleware(['role:nasabah'])->prefix('nasabah')->group(function () {
    Route::get('/dashboard', [NasabahDashboardController::class, 'index']);
    Route::get('/mutasi', [MutasiController::class, 'index']);
    Route::get('/statement', [MutasiController::class, 'statement']);
});
