<?php

namespace App\Actions\ImportExport\Export;

use App\Actions\ImportExport\Contracts\Exporter;
use App\Models\Category;
use App\Models\Product;
use App\Support\ReportPeriod;

/**
 * Kolomnya sengaja sama persis dengan template impor produk: hasil ekspor bisa
 * langsung diedit di Excel lalu diunggah kembali tanpa menata ulang kolom.
 */
class ProductExport implements Exporter
{
    public function filename(ReportPeriod $period): string
    {
        return 'produk';
    }

    public function headers(): array
    {
        return ['sku', 'barcode', 'nama', 'kategori', 'harga', 'modal', 'lacak_stok', 'stok', 'aktif'];
    }

    public function rows(ReportPeriod $period): iterable
    {
        $categories = Category::query()->whereNull('deleted_at')->pluck('name', 'id');

        foreach (Product::query()->whereNull('deleted_at')->orderBy('name')->lazy() as $product) {
            yield [
                $product->sku,
                $product->barcode,
                $product->name,
                $categories[$product->category_id] ?? null,
                (int) $product->price,
                (int) $product->cost,
                $product->track_stock ? 'ya' : 'tidak',
                (int) $product->stock,
                $product->active ? 'ya' : 'tidak',
            ];
        }
    }
}
