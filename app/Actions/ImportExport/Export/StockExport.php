<?php

namespace App\Actions\ImportExport\Export;

use App\Actions\ImportExport\Contracts\Exporter;
use App\Models\Product;
use App\Support\ReportPeriod;

/**
 * Berkas yang sama dipakai dua arah: unduh untuk dibawa berhitung stok, isi
 * kolom `stok_fisik`, lalu unggah lagi sebagai opname. Karena itu kolom stok
 * hasil hitungan dibiarkan kosong — biar tidak ada yang lupa menggantinya.
 */
class StockExport implements Exporter
{
    public function filename(ReportPeriod $period): string
    {
        return 'stok-opname';
    }

    public function headers(): array
    {
        return ['sku', 'barcode', 'nama', 'stok_sistem', 'stok_fisik'];
    }

    public function rows(ReportPeriod $period): iterable
    {
        $products = Product::query()
            ->whereNull('deleted_at')
            ->where('track_stock', 1)
            ->orderBy('name');

        foreach ($products->lazy() as $product) {
            yield [$product->sku, $product->barcode, $product->name, (int) $product->stock, null];
        }
    }
}
