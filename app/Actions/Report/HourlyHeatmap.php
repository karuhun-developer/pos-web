<?php

namespace App\Actions\Report;

use App\Models\Sale;
use App\Support\DisplayTime;
use App\Support\ReportPeriod;

/**
 * Jam ramai: omzet per (hari dalam minggu × jam). Bentuknya heatmap karena
 * yang dibaca adalah magnitudo di sebuah grid — 168 sel tidak mungkin dibaca
 * sebagai batang.
 */
class HourlyHeatmap
{
    private const DAYS = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

    /** @return array<string,mixed> */
    public function handle(ReportPeriod $period): array
    {
        $grid = [];
        $max = 0;

        $sales = Sale::query()
            ->whereNull('deleted_at')
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$period->startMs(), $period->endMs()])
            ->get(['total', 'sold_at']);

        foreach ($sales as $sale) {
            $local = DisplayTime::toLocal((int) $sale->sold_at);
            $day = $local->dayOfWeekIso - 1; // 0 = Senin
            $hour = $local->hour;

            $grid["$day:$hour"] = ($grid["$day:$hour"] ?? 0) + (int) $sale->total;
            $max = max($max, $grid["$day:$hour"]);
        }

        // ECharts heatmap ingin [x, y, value]; sel kosong sengaja tetap dikirim
        // sebagai 0 agar gridnya utuh, bukan berlubang.
        $cells = [];
        foreach (range(0, 6) as $day) {
            foreach (range(0, 23) as $hour) {
                $cells[] = [$hour, $day, $grid["$day:$hour"] ?? 0];
            }
        }

        return [
            'days' => self::DAYS,
            'hours' => array_map(fn (int $hour) => sprintf('%02d', $hour), range(0, 23)),
            'cells' => $cells,
            'max' => $max,
        ];
    }
}
