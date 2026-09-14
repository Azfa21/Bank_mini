<?php

use App\Http\Controllers\AccountingReportController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SystemConfigController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\Nasabah\DashboardController as NasabahDashboardController;
use App\Http\Controllers\Nasabah\MutasiController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\Supervisor\CorrectionController;
use App\Http\Controllers\Supervisor\DashboardController as SupervisorDashboardController;
use App\Http\Controllers\Supervisor\ReportController;
use App\Http\Controllers\Teller\ClosingController;
use App\Http\Controllers\Teller\CustomerRegistrationController;
use App\Http\Controllers\Teller\CustomerSearchController;
use App\Http\Controllers\Teller\PassbookController;
use App\Http\Controllers\Teller\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['role:admin,teller,supervisor,nasabah'])->group(function () {
    Route::get('/qr', [QrCodeController::class, 'show'])->name('qr.show');
    Route::get('/qr/download', [QrCodeController::class, 'download'])->name('qr.download');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login')->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password/staff', [ForgotPasswordController::class, 'resetStaffPassword'])->middleware('throttle:forgot-password')->name('password.staff.reset');
Route::post('/forgot-password/nasabah', [ForgotPasswordController::class, 'resetNasabahPin'])->middleware('throttle:forgot-password')->name('password.nasabah.reset');

Route::middleware(['role:admin'])->prefix('admin')->as('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::resource('users', UserController::class)->except(['show']);
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    Route::get('customers/import', [CustomerController::class, 'importForm'])->name('customers.import.form');
    Route::get('customers/import/template', [CustomerController::class, 'importTemplate'])->name('customers.import.template');
    Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');
    Route::resource('customers', CustomerController::class)->except(['show', 'destroy']);
    Route::patch('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');

    Route::get('accounts/{account}/card/print', [AccountController::class, 'printCard'])->name('accounts.card-print');
    Route::get('accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');

    Route::get('config', [SystemConfigController::class, 'edit'])->name('config.edit');
    Route::put('config', [SystemConfigController::class, 'update'])->name('config.update');

    Route::get('journals', [JournalController::class, 'index'])->name('journals.index');
    Route::get('reports/general-ledger', [AccountingReportController::class, 'generalLedger'])->name('reports.general-ledger');
    Route::get('reports/trial-balance', [AccountingReportController::class, 'trialBalance'])->name('reports.trial-balance');
    Route::get('reports/income-statement', [AccountingReportController::class, 'incomeStatement'])->name('reports.income-statement');
    Route::get('reports/balance-sheet', [AccountingReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
    Route::get('reports/export/{type?}', [AccountingReportController::class, 'exportCsv'])->name('reports.export');
});

Route::middleware(['role:teller'])->prefix('teller')->as('teller.')->group(function () {
    Route::get('/dashboard', fn () => view('teller.dashboard'))->name('dashboard');

    Route::get('/customers/search', [CustomerSearchController::class, 'search'])->name('customers.search');
    Route::get('/customers/lookup', [CustomerSearchController::class, 'lookup'])->name('customers.lookup');
    Route::get('/customers/register', [CustomerRegistrationController::class, 'create'])->name('customers.register');
    Route::post('/customers/register', [CustomerRegistrationController::class, 'store'])->name('customers.register.store');
    Route::get('/customers/{account}', [CustomerSearchController::class, 'show'])->name('customers.show');

    Route::get('/deposits/{account}', [TransactionController::class, 'createDeposit'])->name('deposits.create');
    Route::post('/deposits/{account}', [TransactionController::class, 'storeDeposit'])->name('deposits.store');

    Route::get('/withdrawals/{account}', [TransactionController::class, 'createWithdrawal'])->name('withdrawals.create');
    Route::post('/withdrawals/{account}', [TransactionController::class, 'storeWithdrawal'])->name('withdrawals.store');

    Route::get('/transfers/{account}', [TransactionController::class, 'createTransfer'])->name('transfers.create');
    Route::post('/transfers/{account}', [TransactionController::class, 'storeTransfer'])->name('transfers.store');

    Route::get('/passbook/{account}', [PassbookController::class, 'show'])->name('passbook.show');
    Route::get('/passbook/{account}/print', [PassbookController::class, 'print'])->name('passbook.print');

    Route::get('/transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])->name('transactions.receipt');

    Route::get('/closing', [ClosingController::class, 'index'])->name('closing.index');
    Route::post('/closing', [ClosingController::class, 'store'])->name('closing.store');
    Route::get('/closing/reports/{report}/print', [ClosingController::class, 'print'])->name('closing.print');
});

Route::middleware(['role:supervisor'])->prefix('supervisor')->group(function () {
    Route::get('/dashboard', [SupervisorDashboardController::class, 'index'])->name('supervisor.dashboard');

    Route::get('/reports', [ReportController::class, 'index'])->name('supervisor.reports.index');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('supervisor.reports.show');
    Route::post('/reports/{report}/approve', [ReportController::class, 'approve'])->name('supervisor.reports.approve');
    Route::post('/reports/{report}/reject', [ReportController::class, 'reject'])->name('supervisor.reports.reject');

    Route::post('/transactions/{transaction}/reverse', [CorrectionController::class, 'store'])->name('supervisor.transactions.reverse');

    Route::get('/journals', [JournalController::class, 'index'])->name('supervisor.journals.index');
    Route::get('/reports-accounting/general-ledger', [AccountingReportController::class, 'generalLedger'])->name('supervisor.reports.general-ledger');
    Route::get('/reports-accounting/trial-balance', [AccountingReportController::class, 'trialBalance'])->name('supervisor.reports.trial-balance');
    Route::get('/reports-accounting/income-statement', [AccountingReportController::class, 'incomeStatement'])->name('supervisor.reports.income-statement');
    Route::get('/reports-accounting/balance-sheet', [AccountingReportController::class, 'balanceSheet'])->name('supervisor.reports.balance-sheet');
    Route::get('/reports-accounting/export/{type?}', [AccountingReportController::class, 'exportCsv'])->name('supervisor.reports.export');
});

Route::middleware(['role:nasabah'])->prefix('nasabah')->group(function () {
    Route::get('/dashboard', [NasabahDashboardController::class, 'index'])->name('nasabah.dashboard');
    Route::get('/card/print', [NasabahDashboardController::class, 'printCard'])->name('nasabah.card.print');
    Route::get('/mutasi', [MutasiController::class, 'index'])->name('nasabah.mutasi');
    Route::get('/statement', [MutasiController::class, 'statement'])->name('nasabah.statement');
});
