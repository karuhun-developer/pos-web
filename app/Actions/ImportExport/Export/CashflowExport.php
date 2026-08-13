<?php

namespace App\Actions\ImportExport\Export;

use App\Actions\ImportExport\Contracts\Exporter;
use App\Models\CashflowCategory;
use App\Models\CashflowEntry;
use App\Support\DisplayTime;
use App\Support\ReportPeriod;

class CashflowExport implements Exporter
{
    public function filename(ReportPeriod $period): string
    {
        return "arus-kas-{$period->from->format('Ymd')}-{$period->to->format('Ymd')}";
    }

    public function headers(): array
    {
        return ['tanggal', 'jenis', 'kategori', 'jumlah', 'catatan', 'sumber'];
    }

    public function rows(ReportPeriod $period): iterable
    {
        $categories = CashflowCategory::query()->pluck('name', 'id');

        $entries = CashflowEntry::query()
            ->whereNull('deleted_at')
            ->whereBetween('occurred_at', [$period->startMs(), $period->endMs()])
            ->orderBy('occurred_at');

        foreach ($entries->lazy() as $entry) {
            yield [
                DisplayTime::toLocal((int) $entry->occurred_at)->format('Y-m-d'),
                // debit = uang masuk, credit = uang keluar; di berkas ditulis
                // dengan kata yang dipakai user, bukan istilah akuntansinya.
                $entry->direction === 'debit' ? 'masuk' : 'keluar',
                $categories[$entry->category_id] ?? null,
                (int) $entry->amount,
                $entry->note,
                $entry->source,
            ];
        }
    }
}
