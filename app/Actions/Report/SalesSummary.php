<?php

namespace App\Actions\Report;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\DisplayTime;
use App\Support\ReportPeriod;
use Illuminate\Support\Collection;

/**
 * KPI utama + tren harian, berikut pembanding periode sebelumnya.
 *
 * Pemotongan hari dilakukan di PHP (zona toko), bukan lewat fungsi tanggal SQL:
 * timestamp-nya epoch ms dan sintaksnya beda-beda per driver, jadi menghitungnya
 * di aplikasi membuat hasilnya sama di SQLite (test) maupun MySQL (produksi)
 * dan tidak pernah memotong hari di tengah malam UTC.
 */
class SalesSummary
{
    /** @return array<string,mixed> */
    public function handle(ReportPeriod $period): array
    {
        $current = $this->collect($period);
        $previous = $this->collect($period->previous());

        return [
            'kpi' => [
                'revenue' => $current['revenue'],
                'orders' => $current['orders'],
                'basket' => $current['orders'] > 0 ? intdiv($current['revenue'], $current['orders']) : 0,
                'profit' => $current['profit'],
                'delta' => [
                    'revenue' => $this->delta($current['revenue'], $previous['revenue']),
                    'orders' => $this->delta($current['orders'], $previous['orders']),
                    'basket' => $this->delta(
                        $current['orders'] > 0 ? intdiv($current['revenue'], $current['orders']) : 0,
                        $previous['orders'] > 0 ? intdiv($previous['revenue'], $previous['orders']) : 0,
                    ),
                    'profit' => $this->delta($current['profit'], $previous['profit']),
                ],
            ],
            'trend' => [
                'days' => $period->dayKeys(),
                // Periode pembanding disejajarkan per indeks hari, bukan per
                // tanggal — supaya dua garis bisa dibandingkan di satu sumbu.
                'current' => array_values($current['daily']),
                'previous' => array_values($previous['daily']),
                'previous_days' => $period->previous()->dayKeys(),
            ],
        ];
    }

    /** @return array{revenue:int,orders:int,profit:int,daily:array<string,int>} */
    private function collect(ReportPeriod $period): array
    {
        $sales = Sale::query()
            ->whereNull('deleted_at')
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$period->startMs(), $period->endMs()])
            ->get(['id', 'total', 'sold_at']);

        $daily = array_fill_keys($period->dayKeys(), 0);

        foreach ($sales as $sale) {
            $key = DisplayTime::dayKey((int) $sale->sold_at);
            if (array_key_exists($key, $daily)) {
                $daily[$key] += (int) $sale->total;
            }
        }

        return [
            'revenue' => (int) $sales->sum('total'),
            'orders' => $sales->count(),
            'profit' => $this->grossProfit($sales->pluck('id')),
            'daily' => $daily,
        ];
    }

    /**
     * Laba kotor = omzet item − (qty × modal produk SAAT INI). Modal tidak
     * ikut di-snapshot di sale_items, jadi angkanya perkiraan — UI menyebutnya
     * "estimasi" supaya tidak dibaca sebagai laba akuntansi.
     *
     * @param  Collection<int,string>  $saleIds
     */
    private function grossProfit(Collection $saleIds): int
    {
        if ($saleIds->isEmpty()) {
            return 0;
        }

        $items = SaleItem::query()
            ->whereNull('deleted_at')
            ->whereIn('sale_id', $saleIds->all())
            ->groupBy('product_id')
            ->selectRaw('product_id, sum(qty) as qty, sum(line_total) as revenue')
            ->get();

        $costs = Product::query()
            ->whereIn('id', $items->pluck('product_id')->filter()->all())
            ->pluck('cost', 'id');

        $profit = 0;
        foreach ($items as $item) {
            $cost = (int) ($costs[$item->product_id] ?? 0);
            $profit += (int) $item->revenue - $cost * (int) $item->qty;
        }

        return $profit;
    }

    /** Perubahan dalam persen; null kalau tidak ada pembanding (bukan 0%). */
    private function delta(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
