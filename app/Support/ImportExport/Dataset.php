<?php

namespace App\Support\ImportExport;

use App\Actions\ImportExport\Contracts\Exporter;
use App\Actions\ImportExport\Contracts\Importer;
use App\Actions\ImportExport\Export\CashflowExport;
use App\Actions\ImportExport\Export\CategoryExport;
use App\Actions\ImportExport\Export\ProductExport;
use App\Actions\ImportExport\Export\SaleExport;
use App\Actions\ImportExport\Export\SessionExport;
use App\Actions\ImportExport\Export\StockExport;
use App\Actions\ImportExport\Export\SummaryExport;
use App\Actions\ImportExport\Import\CashflowImport;
use App\Actions\ImportExport\Import\CategoryImport;
use App\Actions\ImportExport\Import\ProductImport;
use App\Actions\ImportExport\Import\StockImport;

/**
 * Daftar dataset yang bisa diunduh/diunggah. Enum, bukan array config: nilainya
 * dipakai langsung sebagai segmen URL, jadi dataset yang tidak dikenal gugur di
 * route-binding — bukan jadi cabang if di dalam controller.
 */
enum Dataset: string
{
    case Products = 'produk';
    case Categories = 'kategori';
    case Sales = 'transaksi';
    case Cashflow = 'kas';
    case Sessions = 'sesi-kasir';
    case Stock = 'stok';
    case Summary = 'ringkasan';

    public function label(): string
    {
        return match ($this) {
            self::Products => 'Produk',
            self::Categories => 'Kategori',
            self::Sales => 'Transaksi',
            self::Cashflow => 'Arus kas',
            self::Sessions => 'Sesi kasir',
            self::Stock => 'Stok opname',
            self::Summary => 'Ringkasan laporan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Products => 'Katalog lengkap dengan harga, modal, dan stok.',
            self::Categories => 'Nama, warna, dan urutan tampil kategori.',
            self::Sales => 'Satu baris per item struk, kolom struknya ikut diulang.',
            self::Cashflow => 'Catatan uang masuk dan keluar pada rentang terpilih.',
            self::Sessions => 'Riwayat buka/tutup laci berikut selisihnya.',
            self::Stock => 'Sisa stok sekarang; berkas yang sama dipakai untuk opname.',
            self::Summary => 'Rekap harian: omzet, transaksi, uang masuk, uang keluar.',
        };
    }

    /** Dataset yang isinya bergantung rentang tanggal di halaman laporan. */
    public function usesPeriod(): bool
    {
        return in_array($this, [self::Sales, self::Cashflow, self::Sessions, self::Summary], true);
    }

    /** @return class-string<Exporter> */
    public function exporter(): string
    {
        return match ($this) {
            self::Products => ProductExport::class,
            self::Categories => CategoryExport::class,
            self::Sales => SaleExport::class,
            self::Cashflow => CashflowExport::class,
            self::Sessions => SessionExport::class,
            self::Stock => StockExport::class,
            self::Summary => SummaryExport::class,
        };
    }

    /**
     * Permission yang harus dipegang untuk MENGUNGGAH dataset ini. Mengunduh
     * cukup dengan reports.view; menulis massal jelas lebih berbahaya, jadi
     * izinnya mengikuti izin menulis data aslinya.
     */
    public function importPermission(): ?string
    {
        return match ($this) {
            self::Products, self::Categories, self::Stock => 'catalog.manage',
            self::Cashflow => 'cashflow.manage',
            default => null,
        };
    }

    /** @return class-string<Importer>|null null = ekspor saja */
    public function importer(): ?string
    {
        return match ($this) {
            self::Products => ProductImport::class,
            self::Categories => CategoryImport::class,
            self::Cashflow => CashflowImport::class,
            self::Stock => StockImport::class,
            default => null,
        };
    }
}
