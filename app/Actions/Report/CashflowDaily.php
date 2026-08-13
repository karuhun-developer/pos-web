<?php

namespace App\Actions\Report;

use App\Models\CashflowEntry;
use App\Support\DisplayTime;
use App\Support\ReportPeriod;

/**
 * Uang masuk vs keluar per hari, digambar sebagai diverging bar di sekitar
 * nol: yang dibaca di sini polaritasnya (hari ini surplus atau defisit),
 * bukan sekadar besarannya.
 */
class CashflowDaily
{
    /** @return array<string,mixed> */
    public function handle(ReportPeriod $period): array
    {
        $days = $period->dayKeys();
        $income = array_fill_keys($days, 0);
        $expense = array_fill_keys($days, 0);

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

        return [
            'days' => $days,
            'income' => array_values($income),
            // Dikirim negatif supaya batangnya turun di bawah garis nol —
            // pembalikan tandanya dilakukan sekali di sini, bukan di tiap chart.
            'expense' => array_map(fn (int $value) => -$value, array_values($expense)),
            'net' => array_map(
                fn (string $day) => $income[$day] - $expense[$day],
                $days,
            ),
        ];
    }
}
