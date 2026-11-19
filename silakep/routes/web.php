<?php

use App\Http\Controllers\DokumenKgbController;
use App\Http\Controllers\PublikController;
use Illuminate\Support\Facades\Route;

// Publik (tanpa login)
Route::get('/', [PublikController::class, 'beranda'])->name('beranda');
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/lacak', [PublikController::class, 'lacak'])->name('lacak');
    Route::get('/verifikasi', [PublikController::class, 'cariSk'])->name('verifikasi.cari');
    Route::get('/verifikasi/{token}', [PublikController::class, 'verifikasi'])->name('verifikasi.sk');
});

// Dokumen (wajib login)
Route::middleware('auth')->group(function () {
    Route::get('/sk-kgb/{usulan}', [DokumenKgbController::class, 'unduhSk'])->name('sk-kgb.unduh');
    Route::get('/berkas-kgb/{usulan}/{jenis}', [DokumenKgbController::class, 'lihatBerkas'])
        ->whereIn('jenis', ['sk_terakhir', 'skp'])->name('berkas-kgb.lihat');
});
