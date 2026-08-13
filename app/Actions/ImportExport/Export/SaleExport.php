<?php

namespace App\Actions\ImportExport\Export;

use App\Actions\ImportExport\Contracts\Exporter;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\DisplayTime;
use App\Support\ReportPeriod;

/**
 * Satu baris per item struk, dengan kolom struknya diulang di tiap baris.
 *
 * Bentuk "rata" ini dipilih supaya berkasnya bisa langsung dipivot di Excel;
 * struk tanpa item (mestinya tidak ada, tapi bisa terjadi kalau item-nya
 * terhapus) tetap muncul satu baris agar totalnya tidak hilang dari rekap.
 */
class SaleExport implements Exporter
{
    public function filename(ReportPeriod $period): string
    {
        return "transaksi-{$period->from->format('Ymd')}-{$period->to->format('Ymd')}";
    }

    public function headers(): array
    {
        return [
            'nomor', 'waktu', 'status', 'metode_bayar', 'kasir_sesi',
            'produk', 'qty', 'harga_satuan', 'diskon_item', 'subtotal_item',
            'subtotal_struk', 'diskon_struk', 'pajak', 'total_struk',
        ];
    }

    public function rows(ReportPeriod $period): iterable
    {
        $sales = Sale::query()
            ->whereNull('deleted_at')
            ->whereBetween('sold_at', [$period->startMs(), $period->endMs()])
            ->orderBy('sold_at');

        foreach ($sales->lazy() as $sale) {
            $items = SaleItem::query()
                ->whereNull('deleted_at')
                ->where('sale_id', $sale->id)
                ->get();

            $header = [
                $sale->number,
                DisplayTime::toLocal((int) $sale->sold_at)->format('Y-m-d H:i:s'),
                $sale->status,
                $sale->payment_method,
                $sale->session_id,
            ];

            $footer = [
                (int) $sale->subtotal,
                (int) $sale->discount,
                (int) $sale->tax,
                (int) $sale->total,
            ];

            if ($items->isEmpty()) {
                yield [...$header, null, null, null, null, null, ...$footer];

                continue;
            }

            foreach ($items as $item) {
                yield [
                    ...$header,
                    $item->name_snapshot,
                    (int) $item->qty,
                    (int) $item->price_snapshot,
                    (int) $item->discount,
                    (int) $item->line_total,
                    ...$footer,
                ];
            }
        }
    }
}
