<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Rentang tanggal laporan (inklusif, menurut jam toko) berikut periode
 * pembandingnya. Satu objek ini men-scope SELURUH halaman laporan supaya
 * setiap panel menghitung angka dari rentang yang sama — kalau tiap chart
 * menghitung rentangnya sendiri, KPI dan grafiknya bisa saling bertentangan.
 */
final class ReportPeriod
{
    public const PRESETS = ['today', '7d', '30d', '90d', 'custom'];

    private function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly string $preset,
    ) {}

    public static function make(?string $preset, ?string $from, ?string $to): self
    {
        $today = DisplayTime::now()->startOfDay();
        $preset = in_array($preset, self::PRESETS, true) ? $preset : '30d';

        if ($preset === 'custom' && $from && $to) {
            $start = CarbonImmutable::parse($from, DisplayTime::zone())->startOfDay();
            $end = CarbonImmutable::parse($to, DisplayTime::zone())->startOfDay();

            // Rentang terbalik dibetulkan, bukan ditolak — user cuma salah urut.
            return new self($start->min($end), $start->max($end), 'custom');
        }

        $span = match ($preset) {
            'today' => 1,
            '7d' => 7,
            '90d' => 90,
            default => 30,
        };

        return new self($today->subDays($span - 1), $today, $preset === 'custom' ? '30d' : $preset);
    }

    public function days(): int
    {
        return $this->from->diffInDays($this->to) + 1;
    }

    public function startMs(): int
    {
        return $this->from->startOfDay()->getTimestampMs();
    }

    public function endMs(): int
    {
        return $this->to->endOfDay()->getTimestampMs();
    }

    /** Periode sebanding tepat sebelum periode ini (panjangnya sama). */
    public function previous(): self
    {
        $days = $this->days();

        return new self($this->from->subDays($days), $this->from->subDay(), $this->preset);
    }

    /**
     * Semua kunci hari ("Y-m-d") dalam rentang — dipakai untuk mengisi hari
     * kosong dengan nol supaya garis tren tidak "melompat" melewati hari sepi.
     *
     * @return list<string>
     */
    public function dayKeys(): array
    {
        $keys = [];
        for ($day = $this->from; $day->lessThanOrEqualTo($this->to); $day = $day->addDay()) {
            $keys[] = $day->format('Y-m-d');
        }

        return $keys;
    }

    /** @return array{preset:string,from:string,to:string,days:int} */
    public function toArray(): array
    {
        return [
            'preset' => $this->preset,
            'from' => $this->from->format('Y-m-d'),
            'to' => $this->to->format('Y-m-d'),
            'days' => $this->days(),
        ];
    }
}
