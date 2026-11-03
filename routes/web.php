<?php

use App\Http\Controllers\AccountingReportController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\CashRequestController as AdminCashRequestController;
use App\Http\Controllers\Admin\ClassPromotionController;
use App\Http\Controllers\Admin\CorrectionController;
use App\Http\Controllers\Admin\DailyReportController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GraduationController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\SaverController;
use App\Http\Controllers\Admin\SystemConfigController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\MonthlyReportController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\Teller\AccountClosureController as TellerAccountClosureController;
use App\Http\Controllers\Teller\CashRequestController as TellerCashRequestController;
use App\Http\Controllers\Teller\ClosingController;
use App\Http\Controllers\Teller\MemberActivationController;
use App\Http\Controllers\Teller\MemberRegistrationController;
use App\Http\Controllers\Teller\MemberSearchController;
use App\Http\Controllers\Teller\PassbookController;
use App\Http\Controllers\Teller\TransactionController;
use App\Services\DailyReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['role:admin,teller'])->group(function () {
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

    Route::get('accounts/{account}/card/print', [AccountController::class, 'printCard'])->name('accounts.card-print');
    Route::get('accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');

    Route::get('graduations', [GraduationController::class, 'index'])->name('graduations.index');
    Route::get('graduations/export', [GraduationController::class, 'export'])->name('graduations.export');
    Route::post('graduations', [GraduationController::class, 'store'])->name('graduations.store');
    Route::post('graduations/closures/{closure}/approve', [GraduationController::class, 'approveClosure'])->name('graduations.closures.approve');
    Route::post('graduations/closures/{closure}/reject', [GraduationController::class, 'rejectClosure'])->name('graduations.closures.reject');
    Route::post('graduations/{member}/not-graduate', [GraduationController::class, 'notGraduate'])->name('graduations.not-graduate');
    Route::post('graduations/{member}', [GraduationController::class, 'graduateMember'])->name('graduations.member');

    Route::get('class-promotions', [ClassPromotionController::class, 'index'])->name('class-promotions.index');
    Route::post('class-promotions', [ClassPromotionController::class, 'store'])->name('class-promotions.store');

    Route::get('config', [SystemConfigController::class, 'edit'])->name('config.edit');
    Route::put('config', [SystemConfigController::class, 'update'])->name('config.update');

    Route::get('journals', [JournalController::class, 'index'])->name('journals.index');

    Route::get('daily-reports', [DailyReportController::class, 'index'])->name('daily-reports.index');
    Route::get('daily-reports/{report}', [DailyReportController::class, 'show'])->name('daily-reports.show');
    Route::post('daily-reports/{report}/approve', [DailyReportController::class, 'approve'])->name('daily-reports.approve');
    Route::post('daily-reports/{report}/reject', [DailyReportController::class, 'reject'])->name('daily-reports.reject');

    Route::post('transactions/{transaction}/reverse', [CorrectionController::class, 'store'])->name('transactions.reverse');

    Route::get('cash-requests', [AdminCashRequestController::class, 'index'])->name('cash-requests.index');
    Route::post('cash-requests/{cashRequest}/approve', [AdminCashRequestController::class, 'approve'])->name('cash-requests.approve');
    Route::post('cash-requests/{cashRequest}/reject', [AdminCashRequestController::class, 'reject'])->name('cash-requests.reject');

    Route::get('reports/monthly', [MonthlyReportController::class, 'index'])->name('reports.monthly');
    Route::get('reports/monthly/print', [MonthlyReportController::class, 'print'])->name('reports.monthly.print');
    Route::get('reports/general-ledger', [AccountingReportController::class, 'generalLedger'])->name('reports.general-ledger');

    // Neraca saldo sekarang satu halaman dengan buku besar. Tautan lama tetap hidup:
    // filter "date" dipetakan ke "date_to" agar bookmark lama menunjuk rentang yang sama.
    Route::get('reports/trial-balance', function (Request $request) {
        $query = $request->query();

        if (array_key_exists('date', $query)) {
            $query['date_to'] = $query['date'];
            unset($query['date']);
        }

        return redirect()->route('admin.reports.general-ledger', $query);
    })->name('reports.trial-balance');
    Route::get('reports/export/{type?}', [AccountingReportController::class, 'exportCsv'])->name('reports.export');
});

Route::middleware(['role:teller'])->prefix('teller')->as('teller.')->group(function () {
    Route::get('/dashboard', function (Request $request, DailyReportService $reports) {
        // Saldo laci tampil di dashboard agar teller tahu saatnya mengajukan modal.
        $recap = $reports->recap($request->user(), now()->toDateString());

        return view('teller.dashboard', compact('recap'));
    })->name('dashboard');

    Route::get('/cash-requests', [TellerCashRequestController::class, 'index'])->name('cash-requests.index');
    Route::post('/cash-requests', [TellerCashRequestController::class, 'store'])->name('cash-requests.store');

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

    Route::get('/accounts/{account}/closure', [TellerAccountClosureController::class, 'create'])->name('accounts.closure.create');
    Route::post('/accounts/{account}/closure', [TellerAccountClosureController::class, 'store'])->name('accounts.closure.store');

    Route::get('/transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])->name('transactions.receipt');

    Route::get('/closing', [ClosingController::class, 'index'])->name('closing.index');
    Route::post('/closing', [ClosingController::class, 'store'])->name('closing.store');
    Route::get('/closing/reports/{report}/print', [ClosingController::class, 'print'])->name('closing.print');
});
