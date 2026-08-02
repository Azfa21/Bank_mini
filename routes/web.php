<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\SystemConfigController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\Nasabah\DashboardController as NasabahDashboardController;
use App\Http\Controllers\Nasabah\MutasiController;
use App\Http\Controllers\Supervisor\ReportController;
use App\Http\Controllers\Teller\ClosingController;
use App\Http\Controllers\Teller\CustomerSearchController;
use App\Http\Controllers\Teller\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login')->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['role:admin'])->prefix('admin')->as('admin.')->group(function () {
    Route::get('/dashboard', fn () => view('admin.dashboard'))->name('dashboard');

    Route::resource('users', UserController::class)->except(['show']);
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    Route::resource('customers', CustomerController::class)->except(['show']);

    Route::get('accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');

    Route::get('config', [SystemConfigController::class, 'edit'])->name('config.edit');
    Route::put('config', [SystemConfigController::class, 'update'])->name('config.update');

    Route::get('journals', [JournalController::class, 'index'])->name('journals.index');
});

Route::middleware(['role:teller'])->prefix('teller')->as('teller.')->group(function () {
    Route::get('/dashboard', fn () => view('teller.dashboard'))->name('dashboard');

    Route::get('/customers/search', [CustomerSearchController::class, 'search'])->name('customers.search');
    Route::get('/customers/lookup', [CustomerSearchController::class, 'lookup'])->name('customers.lookup');
    Route::get('/customers/{account}', [CustomerSearchController::class, 'show'])->name('customers.show');

    Route::get('/deposits/{account}', [TransactionController::class, 'createDeposit'])->name('deposits.create');
    Route::post('/deposits/{account}', [TransactionController::class, 'storeDeposit'])->name('deposits.store');

    Route::get('/withdrawals/{account}', [TransactionController::class, 'createWithdrawal'])->name('withdrawals.create');
    Route::post('/withdrawals/{account}', [TransactionController::class, 'storeWithdrawal'])->name('withdrawals.store');

    Route::get('/transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])->name('transactions.receipt');

    Route::get('/closing', [ClosingController::class, 'index'])->name('closing.index');
    Route::post('/closing', [ClosingController::class, 'store'])->name('closing.store');
    Route::get('/closing/reports/{report}/print', [ClosingController::class, 'print'])->name('closing.print');
});

Route::middleware(['role:supervisor'])->prefix('supervisor')->group(function () {
    Route::get('/dashboard', fn () => view('supervisor.dashboard'))->name('supervisor.dashboard');

    Route::get('/reports', [ReportController::class, 'index'])->name('supervisor.reports.index');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('supervisor.reports.show');
    Route::post('/reports/{report}/approve', [ReportController::class, 'approve'])->name('supervisor.reports.approve');
    Route::post('/reports/{report}/reject', [ReportController::class, 'reject'])->name('supervisor.reports.reject');

    Route::get('/journals', [JournalController::class, 'index'])->name('supervisor.journals.index');
});

Route::middleware(['role:nasabah'])->prefix('nasabah')->group(function () {
    Route::get('/dashboard', [NasabahDashboardController::class, 'index'])->name('nasabah.dashboard');
    Route::get('/mutasi', [MutasiController::class, 'index'])->name('nasabah.mutasi');
});
