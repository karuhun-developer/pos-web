<?php

namespace App\Actions\Report;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\ReportPeriod;

/**
 * Produk terlaris. Dipotong di 10 teratas lalu sisanya dijumlahkan jadi
 * "Lainnya" — bukan dibuang diam-diam, supaya totalnya tetap bisa dicocokkan
 * dengan omzet di KPI.
 */
class TopProducts
{
    private const LIMIT = 10;

    /** @return array<string,mixed> */
    public function handle(ReportPeriod $period): array
    {
        $saleIds = Sale::query()
            ->whereNull('deleted_at')
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$period->startMs(), $period->endMs()])
            ->pluck('id');

        if ($saleIds->isEmpty()) {
            return ['rows' => [], 'other' => null];
        }

        // Dikelompokkan per nama snapshot: produk yang sudah dihapus pun tetap
        // terbaca namanya seperti saat terjual.
        $rows = SaleItem::query()
            ->whereNull('deleted_at')
            ->whereIn('sale_id', $saleIds->all())
            ->groupBy('name_snapshot')
            ->selectRaw('name_snapshot as name, sum(qty) as qty, sum(line_total) as revenue')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'name' => (string) $row->name,
                'qty' => (int) $row->qty,
                'revenue' => (int) $row->revenue,
            ]);

        $top = $rows->take(self::LIMIT)->values();
        $rest = $rows->slice(self::LIMIT);

        return [
            'rows' => $top->all(),
            'other' => $rest->isEmpty() ? null : [
                'name' => 'Lainnya ('.$rest->count().' produk)',
                'qty' => (int) $rest->sum('qty'),
                'revenue' => (int) $rest->sum('revenue'),
            ],
        ];
    }
}
