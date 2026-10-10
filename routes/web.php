<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/login',  [AuthController::class, 'tampilFormLogin'])->name('login');
Route::post('/login', [AuthController::class, 'prosesLogin'])->name('login.proses');

// ============================================================
// HALAMAN DASHBOARD
// GET /dashboard → Tampilkan halaman dashboard
// ============================================================
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// ============================================================
// LOGOUT
// POST /logout → Hapus sesi login, kembali ke login
// ============================================================
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Jika ada yang mengakses root (/), arahkan ke /login
Route::get('/', function () {
    return redirect()->route('login');
});
