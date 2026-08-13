<?php

use App\Http\Controllers\Web\Auth\GoogleController;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Auth\RegisterController;
use App\Http\Controllers\Web\DonationController;
use App\Http\Controllers\Web\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('home');

// Donasi terbuka untuk publik — tidak perlu login. Karena bisa ditulis tanpa
// akun, POST-nya dibatasi kecepatannya supaya tabel donasi tidak bisa
// dibanjiri baris palsu.
Route::get('dukung', [DonationController::class, 'index'])->name('donate.index');
Route::post('dukung', [DonationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('donate.store');
Route::get('dukung/selesai/{donation}', [DonationController::class, 'thanks'])->name('donate.thanks');

Route::middleware('guest')->group(function () {
    Route::get('masuk', [LoginController::class, 'create'])->name('login');
    Route::post('masuk', [LoginController::class, 'store']);

    Route::get('daftar', [RegisterController::class, 'create'])->name('register');
    Route::post('daftar', [RegisterController::class, 'store']);

    Route::get('auth/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
});

Route::post('keluar', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
