<?php

use App\Http\Controllers\Web\Admin\AdminDashboardController;
use App\Http\Controllers\Web\Admin\AdminDonationController;
use App\Http\Controllers\Web\Admin\AdminStoreController;
use App\Http\Controllers\Web\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

/*
 * Area platform (lintas toko). Sengaja TANPA middleware 'store': halaman di
 * sini membaca lintas tenant dan melakukannya secara eksplisit dengan
 * withoutGlobalScope(StoreScope::class) di query masing-masing.
 */
Route::middleware(['auth', 'superadmin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::get('toko', [AdminStoreController::class, 'index'])->name('stores.index');
        Route::get('toko/{store}', [AdminStoreController::class, 'show'])->name('stores.show');

        Route::get('pengguna', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('pengguna/{user}/superadmin', [AdminUserController::class, 'toggleSuperadmin'])
            ->name('users.superadmin');

        Route::get('donasi', [AdminDonationController::class, 'index'])->name('donations.index');
        Route::put('donasi/{donation}', [AdminDonationController::class, 'update'])->name('donations.update');
        Route::get('donasi/ekspor/csv', [AdminDonationController::class, 'export'])->name('donations.export');
    });
