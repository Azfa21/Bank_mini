<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', fn () => 'Admin Dashboard')->name('admin.dashboard');
});

Route::middleware(['role:teller'])->prefix('teller')->group(function () {
    Route::get('/dashboard', fn () => 'Teller Dashboard')->name('teller.dashboard');
});

Route::middleware(['role:supervisor'])->prefix('supervisor')->group(function () {
    Route::get('/dashboard', fn () => 'Supervisor Dashboard')->name('supervisor.dashboard');
});

Route::middleware(['role:nasabah'])->prefix('nasabah')->group(function () {
    Route::get('/dashboard', fn () => 'Nasabah Dashboard')->name('nasabah.dashboard');
});
