<?php

namespace App\Actions\ImportExport\Contracts;

use App\Support\ReportPeriod;

/**
 * Satu dataset yang bisa diunduh. Implementasinya WAJIB mengembalikan
 * generator/iterator (bukan array) untuk data bervolume besar supaya
 * ekspornya tetap streaming.
 */
interface Exporter
{
    /** Nama berkas tanpa ekstensi. */
    public function filename(ReportPeriod $period): string;

    /** @return list<string> */
    public function headers(): array;

    /**
     * Dataset yang tidak terikat waktu (produk, kategori, stok) mengabaikan
     * $period — sengaja tetap diterima supaya pemanggilnya seragam.
     *
     * @return iterable<int,list<null|bool|float|int|string>>
     */
    public function rows(ReportPeriod $period): iterable;
}
