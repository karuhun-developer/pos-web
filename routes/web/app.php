<?php

use App\Http\Controllers\Web\CashflowCategoryController;
use App\Http\Controllers\Web\CashflowController;
use App\Http\Controllers\Web\CashierSessionController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ImportExportController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\SaleController;
use App\Http\Controllers\Web\StoreSettingsController;
use App\Http\Controllers\Web\StoreSwitchController;
use Illuminate\Support\Facades\Route;

/*
 * Semua halaman milik pemilik toko. Middleware 'store' (SetCurrentStore) WAJIB
 * ada di sini: StoreScope fail-open — tanpa toko aktif query tidak dibatasi
 * sama sekali, jadi konteks toko harus dipastikan sebelum halaman apa pun
 * menyentuh entity sync.
 */
Route::middleware(['auth', 'store'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::post('toko/aktif/{store}', StoreSwitchController::class)->name('stores.switch');

    // Katalog
    Route::prefix('produk')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('baru', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('{product}/ubah', [ProductController::class, 'edit'])->name('edit');
        Route::put('{product}', [ProductController::class, 'update'])->name('update');
        Route::delete('{product}', [ProductController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('kategori')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::put('{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('{category}', [CategoryController::class, 'destroy'])->name('destroy');
    });

    // Transaksi
    Route::prefix('transaksi')->name('sales.')->group(function () {
        Route::get('/', [SaleController::class, 'index'])->name('index');
        Route::get('{sale}', [SaleController::class, 'show'])->name('show');
        Route::post('{sale}/batal', [SaleController::class, 'void'])->name('void');
    });

    // Arus kas
    Route::prefix('kas')->name('cashflow.')->group(function () {
        Route::get('/', [CashflowController::class, 'index'])->name('index');
        Route::post('/', [CashflowController::class, 'store'])->name('store');
        Route::put('{entry}', [CashflowController::class, 'update'])->name('update');
        Route::delete('{entry}', [CashflowController::class, 'destroy'])->name('destroy');

        Route::post('kategori', [CashflowCategoryController::class, 'store'])->name('categories.store');
        Route::put('kategori/{category}', [CashflowCategoryController::class, 'update'])->name('categories.update');
        Route::delete('kategori/{category}', [CashflowCategoryController::class, 'destroy'])->name('categories.destroy');
    });

    Route::get('sesi-kasir', [CashierSessionController::class, 'index'])->name('sessions.index');

    // Laporan & chart
    Route::get('laporan', [ReportController::class, 'index'])->name('reports.index');

    // Impor/ekspor
    Route::prefix('impor-ekspor')->name('io.')->group(function () {
        Route::get('/', [ImportExportController::class, 'index'])->name('index');
        Route::get('ekspor/{dataset}', [ImportExportController::class, 'export'])->name('export');
        Route::get('template/{dataset}', [ImportExportController::class, 'template'])->name('template');
        Route::post('pratinjau', [ImportExportController::class, 'preview'])->name('preview');
        Route::post('terapkan', [ImportExportController::class, 'commit'])->name('commit');
    });

    // Pengaturan toko
    Route::get('toko', [StoreSettingsController::class, 'edit'])->name('store.edit');
    Route::put('toko', [StoreSettingsController::class, 'update'])->name('store.update');
});
