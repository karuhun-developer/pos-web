<?php

namespace App\Actions\Sale;

use App\Actions\Sync\WriteEntity;
use App\Models\CashflowCategory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\DisplayTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Membatalkan transaksi dari web. Tiga akibat yang harus konsisten:
 * status transaksi, stok produk, dan buku kas.
 *
 * Ledger-nya di-KOREKSI dengan entri lawan (credit), bukan dengan menghapus
 * entri penjualannya. Jejak "pernah ada penjualan lalu dibatalkan" itu yang
 * dicari saat rekonsiliasi laci kasir; menghapus baris aslinya justru bikin
 * angka sesi kasir yang sudah ditutup tidak bisa dijelaskan.
 */
class VoidSale
{
    public function __construct(private readonly WriteEntity $writer) {}

    public function handle(Sale $sale): void
    {
        if ($sale->status === 'void') {
            throw ValidationException::withMessages([
                'sale' => 'Transaksi ini sudah dibatalkan.',
            ]);
        }

        DB::transaction(function () use ($sale) {
            $items = SaleItem::query()->where('sale_id', $sale->id)->whereNull('deleted_at')->get();

            $this->writer->upsert('sales', ['status' => 'void'], $sale->id);
            $this->restoreStock($items);
            $this->writeReversal($sale);
        });
    }

    /** @param  Collection<int,SaleItem>  $items */
    private function restoreStock($items): void
    {
        $products = Product::query()
            ->whereIn('id', $items->pluck('product_id')->filter()->all())
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get($item->product_id);

            // Produk non-track_stock memang tidak menyimpan stok; produk yang
            // sudah dihapus juga dilewati (tombstone-nya tidak perlu dihidupkan).
            if ($product === null || ! $product->track_stock || $product->deleted_at !== null) {
                continue;
            }

            $this->writer->upsert('products', [
                'stock' => (int) $product->stock + (int) $item->qty,
            ], $product->id);
        }
    }

    private function writeReversal(Sale $sale): void
    {
        $category = CashflowCategory::query()
            ->where('type', 'income')
            ->where('is_system', 1)
            ->whereNull('deleted_at')
            ->first();

        $this->writer->upsert('cashflow_entries', [
            'category_id' => $category?->id,
            'session_id' => $sale->session_id,
            'direction' => 'credit', // uang keluar: menganulir pemasukan
            'amount' => (int) $sale->total,
            'source' => 'sale',
            'source_ref' => $sale->id,
            'note' => "Pembatalan {$sale->number}",
            'occurred_at' => DisplayTime::nowMs(),
        ]);
    }
}
