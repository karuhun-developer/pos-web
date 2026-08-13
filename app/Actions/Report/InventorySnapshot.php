<?php

namespace App\Actions\Report;

use App\Models\Product;

/**
 * Stok saat ini. Sengaja BUKAN chart: yang dicari adalah "produk mana yang
 * harus dibeli hari ini" — itu daftar, bukan bentuk. Yang dijadikan angka
 * tunggal hanya nilai inventorinya.
 *
 * Tidak bergantung pada periode: stok adalah kondisi sekarang, bukan rentang.
 */
class InventorySnapshot
{
    private const LOW_STOCK_THRESHOLD = 5;

    /** @return array<string,mixed> */
    public function handle(): array
    {
        $products = Product::query()
            ->whereNull('deleted_at')
            ->where('track_stock', 1)
            ->get(['id', 'name', 'sku', 'stock', 'cost', 'price', 'active']);

        $value = $products->sum(fn (Product $product) => (int) $product->stock * (int) $product->cost);

        $low = $products
            ->filter(fn (Product $product) => (int) $product->stock <= self::LOW_STOCK_THRESHOLD)
            ->sortBy('stock')
            ->values()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'stock' => (int) $product->stock,
                'value' => (int) $product->stock * (int) $product->cost,
                'active' => (int) $product->active,
            ]);

        return [
            'tracked' => $products->count(),
            'value' => (int) $value,
            'threshold' => self::LOW_STOCK_THRESHOLD,
            'out_of_stock' => $products->filter(fn (Product $p) => (int) $p->stock <= 0)->count(),
            'low' => $low->take(20)->all(),
            'low_total' => $low->count(),
        ];
    }
}
