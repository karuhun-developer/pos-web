<?php

namespace App\Actions\ImportExport;

use App\Support\ImportExport\Dataset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Langkah kedua impor: berkas yang tadi dipratinjau dibaca ulang dan baris
 * yang sah ditulis lewat WriteEntity (di dalam importer masing-masing),
 * sehingga hasil impor ikut ter-pull perangkat Android seperti perubahan lain.
 *
 * Baris error dilewati, bukan membatalkan seluruh berkas: 3 baris rusak dari
 * 500 tidak boleh menyandera 497 baris yang benar. Yang dilewati dilaporkan
 * jumlahnya.
 */
class CommitImport
{
    private const DISK = 'local';

    public function __construct(private readonly AnalyseImport $analyse) {}

    /** @return array{applied:int,skipped:int,summary:array<string,int>} */
    public function handle(Dataset $dataset, string $token): array
    {
        $entry = Session::get("imports.{$token}");

        // Token terikat sesi pengunggah dan dataset-nya; kalau tidak cocok,
        // ini bukan berkas milik permintaan ini.
        if (! is_array($entry) || $entry['dataset'] !== $dataset->value) {
            throw ValidationException::withMessages([
                'token' => 'Pratinjau sudah kedaluwarsa. Unggah ulang berkasnya.',
            ]);
        }

        $disk = Storage::disk(self::DISK);

        if (! $disk->exists($entry['path'])) {
            throw ValidationException::withMessages([
                'token' => 'Berkas pratinjau sudah dibersihkan. Unggah ulang berkasnya.',
            ]);
        }

        $extension = pathinfo($entry['path'], PATHINFO_EXTENSION);
        $analysis = $this->analyse->handle($dataset, $disk->path($entry['path']), $extension);
        $importer = $analysis['importer'];

        $applied = 0;

        DB::transaction(function () use ($analysis, $importer, &$applied) {
            foreach ($analysis['rows'] as $row) {
                if ($row['status'] === 'error') {
                    continue;
                }

                $importer->apply($row['analysed']);
                $applied++;
            }
        });

        $disk->delete($entry['path']);
        Session::forget("imports.{$token}");

        return [
            'applied' => $applied,
            'skipped' => $analysis['summary']['error'],
            'summary' => $analysis['summary'],
        ];
    }
}
