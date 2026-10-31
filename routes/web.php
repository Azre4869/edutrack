<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataMasterController;
use App\Http\Controllers\EarlyWarningController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\NilaiController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\RaporController;
use App\Http\Controllers\TindakLanjutController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::get('/', function () {
    if (auth()->check()) {
        $role = auth()->user()->role;
        if ($role === 'Operator') return redirect()->route('operator.dashboard');
        if ($role === 'Wali Kelas') return redirect()->route('wali.dashboard');
        return redirect()->route('guru.dashboard');
    }
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::any('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/quick-login/{role}', [AuthController::class, 'quickLogin'])->name('quick-login');

// Authenticated Routes
Route::middleware(['web'])->group(function () {

    // ==================== OPERATOR ====================
    Route::prefix('operator')->name('operator.')->middleware('role:Operator')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'operator'])->name('dashboard');

        // Master Data
        Route::get('/siswa', [DataMasterController::class, 'siswa'])->name('siswa');
        Route::post('/siswa', [DataMasterController::class, 'storeSiswa'])->name('siswa.store');
        Route::put('/siswa/{id}', [DataMasterController::class, 'updateSiswa'])->name('siswa.update');
        Route::delete('/siswa/{id}', [DataMasterController::class, 'destroySiswa'])->name('siswa.destroy');

        Route::get('/guru', [DataMasterController::class, 'guru'])->name('guru');
        Route::post('/guru', [DataMasterController::class, 'storeGuru'])->name('guru.store');
        Route::put('/guru/{id}', [DataMasterController::class, 'updateGuru'])->name('guru.update');
        Route::delete('/guru/{id}', [DataMasterController::class, 'destroyGuru'])->name('guru.destroy');

        Route::get('/kelas', [DataMasterController::class, 'kelas'])->name('kelas');
        Route::post('/kelas', [DataMasterController::class, 'storeKelas'])->name('kelas.store');
        Route::put('/kelas/{id}', [DataMasterController::class, 'updateKelas'])->name('kelas.update');
        Route::delete('/kelas/{id}', [DataMasterController::class, 'destroyKelas'])->name('kelas.destroy');

        Route::get('/mapel', [DataMasterController::class, 'mapel'])->name('mapel');
        Route::post('/mapel', [DataMasterController::class, 'storeMapel'])->name('mapel.store');
        Route::put('/mapel/{id}', [DataMasterController::class, 'updateMapel'])->name('mapel.update');
        Route::delete('/mapel/{id}', [DataMasterController::class, 'destroyMapel'])->name('mapel.destroy');

        Route::get('/ekskul', [DataMasterController::class, 'ekskul'])->name('ekskul');
        Route::post('/ekskul', [DataMasterController::class, 'storeEkskul'])->name('ekskul.store');
        Route::put('/ekskul/{id}', [DataMasterController::class, 'updateEkskul'])->name('ekskul.update');
        Route::delete('/ekskul/{id}', [DataMasterController::class, 'destroyEkskul'])->name('ekskul.destroy');

        // Nilai
        Route::get('/nilai', [NilaiController::class, 'inputNilai'])->name('nilai');
        Route::post('/nilai', [NilaiController::class, 'storeNilai'])->name('nilai.store');
        Route::get('/capaian', [NilaiController::class, 'inputNilai'])->name('capaian');
        Route::post('/capaian', [NilaiController::class, 'storeCapaian'])->name('capaian.store');

        // Presensi
        Route::get('/presensi-kelas', [PresensiController::class, 'presensiKelas'])->name('presensi-kelas');
        Route::post('/presensi-kelas', [PresensiController::class, 'storePresensiKelas'])->name('presensi-kelas.store');
        Route::get('/presensi-ekskul', [PresensiController::class, 'presensiEkskul'])->name('presensi-ekskul');
        Route::post('/presensi-ekskul', [PresensiController::class, 'storePresensiEkskul'])->name('presensi-ekskul.store');

        // Rapor (Monitoring), Early Warning, Laporan, Notifikasi
        Route::get('/rapor', [RaporController::class, 'index'])->name('rapor');
        Route::get('/early-warning', [EarlyWarningController::class, 'index'])->name('early-warning');
        Route::get('/tindak-lanjut/{id?}', [TindakLanjutController::class, 'index'])->name('tindak-lanjut');
        Route::post('/tindak-lanjut', [TindakLanjutController::class, 'store'])->name('tindak-lanjut.store');
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan');
        Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi');
    });

    // ==================== WALI KELAS ====================
    Route::prefix('wali')->name('wali.')->middleware('role:Wali Kelas')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'wali'])->name('dashboard');

        // Siswa
        Route::get('/siswa', [DataMasterController::class, 'siswa'])->name('siswa');

        // Input Prestasi
        Route::get('/prestasi', [NilaiController::class, 'inputPrestasi'])->name('prestasi');
        Route::post('/prestasi', [NilaiController::class, 'storePrestasi'])->name('prestasi.store');

        // Presensi
        Route::get('/presensi-kelas', [PresensiController::class, 'presensiKelas'])->name('presensi-kelas');
        Route::post('/presensi-kelas', [PresensiController::class, 'storePresensiKelas'])->name('presensi-kelas.store');
        Route::get('/presensi-ekskul', [PresensiController::class, 'presensiEkskul'])->name('presensi-ekskul');
        Route::post('/presensi-ekskul', [PresensiController::class, 'storePresensiEkskul'])->name('presensi-ekskul.store');

        // Rapor
        Route::get('/rapor', [RaporController::class, 'index'])->name('rapor');
        Route::get('/rapor/{id}/cetak', [RaporController::class, 'show'])->name('rapor.cetak');
        Route::get('/rapor/{id}', [RaporController::class, 'show'])->name('rapor.show');

        // Early Warning & Tindak Lanjut
        Route::get('/early-warning', [EarlyWarningController::class, 'index'])->name('early-warning');
        Route::get('/tindak-lanjut/{id?}', [TindakLanjutController::class, 'index'])->name('tindak-lanjut');
        Route::post('/tindak-lanjut', [TindakLanjutController::class, 'store'])->name('tindak-lanjut.store');

        // Notifikasi
        Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi');
    });

    // ==================== GURU MAPEL ====================
    Route::prefix('guru')->name('guru.')->middleware('role:Guru Mapel')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'guru'])->name('dashboard');

        // Siswa
        Route::get('/siswa', [DataMasterController::class, 'siswa'])->name('siswa');

        // Nilai
        Route::get('/nilai', [NilaiController::class, 'inputNilai'])->name('nilai');
        Route::post('/nilai', [NilaiController::class, 'storeNilai'])->name('nilai.store');

        // Capaian
        Route::get('/capaian', [NilaiController::class, 'inputNilai'])->name('capaian');
        Route::get('/capaian/detail', [NilaiController::class, 'getCapaian'])->name('capaian.detail');
        Route::post('/capaian', [NilaiController::class, 'storeCapaian'])->name('capaian.store');

        // Notifikasi
        Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi');
    });
});
