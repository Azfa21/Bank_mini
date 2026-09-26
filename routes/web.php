<?php

use App\Http\Controllers\AccountingReportController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\SaverController;
use App\Http\Controllers\Admin\SystemConfigController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\MonthlyReportController;
use App\Http\Controllers\Nasabah\DashboardController as NasabahDashboardController;
use App\Http\Controllers\Nasabah\MutasiController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\Supervisor\CorrectionController;
use App\Http\Controllers\Supervisor\DashboardController as SupervisorDashboardController;
use App\Http\Controllers\Supervisor\ReportController;
use App\Http\Controllers\Teller\ClosingController;
use App\Http\Controllers\Teller\MemberActivationController;
use App\Http\Controllers\Teller\MemberRegistrationController;
use App\Http\Controllers\Teller\MemberSearchController;
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
Route::post('/forgot-password/request-otp', [ForgotPasswordController::class, 'requestOtp'])->middleware('throttle:forgot-password')->name('password.otp.request');
Route::post('/forgot-password/reset', [ForgotPasswordController::class, 'resetCredential'])->middleware('throttle:forgot-password')->name('password.reset.submit');

Route::middleware(['role:admin'])->prefix('admin')->as('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::resource('users', UserController::class)->except(['show']);
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    Route::get('members/import', [MemberController::class, 'importForm'])->name('members.import.form');
    Route::get('members/import/template', [MemberController::class, 'importTemplate'])->name('members.import.template');
    Route::post('members/import', [MemberController::class, 'import'])->name('members.import');
    Route::resource('members', MemberController::class)->except(['show']);

    Route::get('savers', [SaverController::class, 'index'])->name('savers.index');
    Route::get('savers/{saver}/edit', [SaverController::class, 'edit'])->name('savers.edit');
    Route::put('savers/{saver}', [SaverController::class, 'update'])->name('savers.update');
    Route::patch('savers/{saver}/toggle-status', [SaverController::class, 'toggleStatus'])->name('savers.toggle-status');
    Route::delete('savers/{saver}', [SaverController::class, 'destroy'])->name('savers.destroy');

    Route::get('accounts/{account}/card/print', [AccountController::class, 'printCard'])->name('accounts.card-print');
    Route::get('accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');

    Route::get('config', [SystemConfigController::class, 'edit'])->name('config.edit');
    Route::put('config', [SystemConfigController::class, 'update'])->name('config.update');

    Route::get('journals', [JournalController::class, 'index'])->name('journals.index');
    Route::get('reports/monthly', [MonthlyReportController::class, 'index'])->name('reports.monthly');
    Route::get('reports/monthly/print', [MonthlyReportController::class, 'print'])->name('reports.monthly.print');
    Route::get('reports/general-ledger', [AccountingReportController::class, 'generalLedger'])->name('reports.general-ledger');
    Route::get('reports/trial-balance', [AccountingReportController::class, 'trialBalance'])->name('reports.trial-balance');
    Route::get('reports/income-statement', [AccountingReportController::class, 'incomeStatement'])->name('reports.income-statement');
    Route::get('reports/export/{type?}', [AccountingReportController::class, 'exportCsv'])->name('reports.export');
});

Route::middleware(['role:teller'])->prefix('teller')->as('teller.')->group(function () {
    Route::get('/dashboard', fn () => view('teller.dashboard'))->name('dashboard');

    Route::get('/members/search', [MemberSearchController::class, 'search'])->name('members.search');
    Route::get('/members/lookup', [MemberSearchController::class, 'lookup'])->name('members.lookup');
    Route::get('/members/register', [MemberRegistrationController::class, 'create'])->name('members.register');
    Route::post('/members/register', [MemberRegistrationController::class, 'store'])->name('members.register.store');
    Route::get('/members/{member}/activate', [MemberActivationController::class, 'create'])->name('members.activate');
    Route::post('/members/{member}/activate', [MemberActivationController::class, 'store'])->name('members.activate.store');
    Route::get('/members/{account}', [MemberSearchController::class, 'show'])->name('members.show');

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
    Route::get('/reports-monthly', [MonthlyReportController::class, 'index'])->name('supervisor.reports.monthly');
    Route::get('/reports-monthly/print', [MonthlyReportController::class, 'print'])->name('supervisor.reports.monthly.print');
    Route::get('/reports-accounting/general-ledger', [AccountingReportController::class, 'generalLedger'])->name('supervisor.reports.general-ledger');
    Route::get('/reports-accounting/trial-balance', [AccountingReportController::class, 'trialBalance'])->name('supervisor.reports.trial-balance');
    Route::get('/reports-accounting/income-statement', [AccountingReportController::class, 'incomeStatement'])->name('supervisor.reports.income-statement');
    Route::get('/reports-accounting/export/{type?}', [AccountingReportController::class, 'exportCsv'])->name('supervisor.reports.export');
});

Route::middleware(['role:nasabah'])->prefix('nasabah')->group(function () {
    Route::get('/dashboard', [NasabahDashboardController::class, 'index'])->name('nasabah.dashboard');
    Route::get('/card/print', [NasabahDashboardController::class, 'printCard'])->name('nasabah.card.print');
    Route::get('/mutasi', [MutasiController::class, 'index'])->name('nasabah.mutasi');
    Route::get('/statement', [MutasiController::class, 'statement'])->name('nasabah.statement');
});
