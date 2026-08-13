<?php

namespace App\Actions\ImportExport\Import\Concerns;

use App\Support\DisplayTime;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;

/**
 * Pembacaan sel yang toleran terhadap kebiasaan Excel di Indonesia: "Rp 12.500",
 * "12.500,00", "1 234", dan kolom kosong yang sebenarnya berisi spasi. Sekalian
 * pembentuk hasil baris yang dipakai semua importer.
 */
trait ParsesValues
{
    /**
     * Baris yang ditolak. Alasannya WAJIB menyebut apa yang harus diperbaiki —
     * "tidak valid" tidak menolong siapa pun yang punya 500 baris.
     *
     * @return array{status:'error',reason:string,label:string,id:null,attributes:array<string,mixed>}
     */
    protected function error(string $label, string $reason): array
    {
        return ['status' => 'error', 'reason' => $reason, 'label' => $label, 'id' => null, 'attributes' => []];
    }

    /** @param array<string,string> $values */
    protected function text(array $values, string $key): ?string
    {
        $value = trim($values[$key] ?? '');

        return $value === '' ? null : $value;
    }

    /**
     * Uang/angka. "Rp 12.500,50" → 12500 (rupiah bulat: unit terkecil di app
     * ini rupiah, bukan sen — pecahannya memang dibuang).
     */
    protected function number(array $values, string $key): ?int
    {
        $raw = $this->text($values, $key);

        if ($raw === null) {
            return null;
        }

        $clean = preg_replace('/[^0-9,.\-]/', '', $raw) ?? '';
        // Pemisah ribuan dibuang, koma desimal dipotong: "12.500,50" → "12500".
        $clean = str_replace('.', '', $clean);
        $clean = explode(',', $clean)[0];

        return is_numeric($clean) ? (int) $clean : null;
    }

    /** "ya/tidak", "1/0", "true/false", "y/n" — semuanya diterima. */
    protected function flag(array $values, string $key, bool $default): bool
    {
        $raw = mb_strtolower($this->text($values, $key) ?? '');

        return match ($raw) {
            '' => $default,
            'ya', 'y', '1', 'true', 'aktif', 'yes' => true,
            default => false,
        };
    }

    /** Tanggal "Y-m-d" atau "d/m/Y" → epoch ms awal hari menurut zona toko. */
    protected function dayStart(array $values, string $key): ?int
    {
        $raw = $this->text($values, $key);

        if ($raw === null) {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $format) {
            try {
                // Carbon 3 melempar, bukan mengembalikan false, kalau formatnya
                // tidak cocok — jadi percobaan formatnya dibungkus try.
                return CarbonImmutable::createFromFormat("!{$format}", $raw, DisplayTime::zone())
                    ->startOfDay()
                    ->getTimestampMs();
            } catch (InvalidFormatException) {
                continue;
            }
        }

        return null;
    }
}
