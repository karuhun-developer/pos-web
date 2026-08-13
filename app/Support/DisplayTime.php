<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Jembatan antara epoch ms (cara data disimpan) dan "hari" menurut jam toko
 * (cara laporan dibaca). Semua pemotongan tanggal lewat sini supaya tidak ada
 * satu pun query yang diam-diam memakai UTC dan menggeser omzet lewat tengah
 * malam ke tanggal yang salah.
 */
class DisplayTime
{
    public static function zone(): string
    {
        return (string) config('app.timezone_display', 'Asia/Jakarta');
    }

    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::zone());
    }

    /** Tanggal "Y-m-d" (atau apa pun yang dimengerti Carbon) → epoch ms 00:00 lokal. */
    public static function startOfDayMs(string $date): int
    {
        return CarbonImmutable::parse($date, self::zone())->startOfDay()->getTimestampMs();
    }

    /** Batas akhir inklusif: 23:59:59.999 lokal. */
    public static function endOfDayMs(string $date): int
    {
        return CarbonImmutable::parse($date, self::zone())->endOfDay()->getTimestampMs();
    }

    public static function toLocal(int $epochMs): CarbonImmutable
    {
        return CarbonImmutable::createFromTimestampMs($epochMs, self::zone());
    }

    /** Kunci bucket harian ("2026-08-13") dari epoch ms. */
    public static function dayKey(int $epochMs): string
    {
        return self::toLocal($epochMs)->format('Y-m-d');
    }

    public static function nowMs(): int
    {
        return (int) round(microtime(true) * 1000);
    }
}
