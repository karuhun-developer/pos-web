<?php

namespace App\Actions\ImportExport\Contracts;

/**
 * Impor dua langkah: setiap baris dinilai dulu (analyse) lalu ditulis (apply).
 *
 * Pratinjau dan penerapan memakai analyse() YANG SAMA — kalau keduanya punya
 * jalur validasi sendiri, apa yang dilihat user di pratinjau bisa berbeda
 * dengan yang benar-benar tersimpan.
 */
interface Importer
{
    /**
     * Kolom template, urut. Kunci = nama header yang dikenali pembaca.
     *
     * @return array<string,string> header => keterangan singkat
     */
    public function columns(): array;

    /**
     * Satu baris contoh untuk template kosong — lebih menolong daripada
     * berkas yang cuma berisi judul kolom.
     *
     * @return list<string>
     */
    public function sample(): array;

    /**
     * Nilai satu baris: baru, diperbarui, atau error berikut alasannya.
     * TIDAK menulis apa pun.
     *
     * @param  array<string,string>  $values
     * @return array{status:'new'|'update'|'error',reason:?string,label:string,id:?string,attributes:array<string,mixed>}
     */
    public function analyse(array $values): array;

    /**
     * Tulis satu baris hasil analyse yang statusnya new/update.
     *
     * @param  array{status:string,id:?string,attributes:array<string,mixed>}  $analysed
     */
    public function apply(array $analysed): void;
}
