<?php

namespace App\Actions\ImportExport\Export;

use App\Actions\ImportExport\Contracts\Exporter;
use App\Models\CashflowEntry;
use App\Models\Sale;
use App\Support\DisplayTime;
use App\Support\ReportPeriod;

/**
 * Rekap harian — versi berkas dari halaman laporan. Hari tanpa transaksi tetap
 * ditulis dengan nol supaya deretnya utuh saat dibuat grafik di Excel.
 *
 * Pemotongan harinya memakai DisplayTime (zona toko), sama seperti chart, jadi
 * angka di berkas dan di layar tidak pernah berbeda sehari.
 */
class SummaryExport implements Exporter
{
    public function filename(ReportPeriod $period): string
    {
        return "ringkasan-{$period->from->format('Ymd')}-{$period->to->format('Ymd')}";
    }

    public function headers(): array
    {
        return ['tanggal', 'transaksi', 'omzet', 'rata_rata_keranjang', 'uang_masuk', 'uang_keluar', 'arus_bersih'];
    }

    public function rows(ReportPeriod $period): iterable
    {
        $days = $period->dayKeys();
        $orders = array_fill_keys($days, 0);
        $revenue = array_fill_keys($days, 0);
        $income = array_fill_keys($days, 0);
        $expense = array_fill_keys($days, 0);

        Sale::query()
            ->whereNull('deleted_at')
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$period->startMs(), $period->endMs()])
            ->get(['total', 'sold_at'])
            ->each(function (Sale $sale) use (&$orders, &$revenue) {
                $key = DisplayTime::dayKey((int) $sale->sold_at);

                if (array_key_exists($key, $orders)) {
                    $orders[$key]++;
                    $revenue[$key] += (int) $sale->total;
                }
            });

        CashflowEntry::query()
            ->whereNull('deleted_at')
            ->whereBetween('occurred_at', [$period->startMs(), $period->endMs()])
            ->get(['direction', 'amount', 'occurred_at'])
            ->each(function (CashflowEntry $entry) use (&$income, &$expense) {
                $key = DisplayTime::dayKey((int) $entry->occurred_at);

                if (! array_key_exists($key, $income)) {
                    return;
                }

                if ($entry->direction === 'debit') {
                    $income[$key] += (int) $entry->amount;

                    return;
                }

                $expense[$key] += (int) $entry->amount;
            });

        foreach ($days as $day) {
            yield [
                $day,
                $orders[$day],
                $revenue[$day],
                $orders[$day] > 0 ? intdiv($revenue[$day], $orders[$day]) : 0,
                $income[$day],
                $expense[$day],
                $income[$day] - $expense[$day],
            ];
        }
    }
}
