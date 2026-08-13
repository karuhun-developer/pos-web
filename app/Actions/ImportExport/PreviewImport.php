<?php

namespace App\Actions\ImportExport;

use App\Support\ImportExport\Dataset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Langkah pertama impor: berkas dibaca dan dinilai, TAPI belum ditulis.
 *
 * Berkasnya disimpan sementara dan dikunci ke sesi pengunggahnya; langkah
 * kedua (CommitImport) membacanya lagi dari sana. Alternatifnya — menyimpan
 * ribuan baris hasil parse di session — akan meledak untuk berkas besar, dan
 * meminta user mengunggah ulang saat konfirmasi membuka celah berkasnya
 * berganti di antara dua langkah.
 */
class PreviewImport
{
    /** Baris yang dikirim ke layar; sisanya cukup diwakili ringkasan. */
    private const PREVIEW_LIMIT = 200;

    private const DISK = 'local';

    public function __construct(private readonly AnalyseImport $analyse) {}

    /** @return array<string,mixed> */
    public function handle(Dataset $dataset, UploadedFile $file): array
    {
        $this->pruneStaleUploads();

        $token = (string) Str::uuid();
        $extension = mb_strtolower($file->getClientOriginalExtension() ?: 'csv');
        $path = "imports/{$token}.{$extension}";

        Storage::disk(self::DISK)->putFileAs('imports', $file, "{$token}.{$extension}");

        // Token hanya berlaku untuk sesi yang mengunggahnya — tanpa ini, token
        // orang lain bisa ditebak dan datanya ikut tertulis ke toko penebak.
        Session::put("imports.{$token}", ['dataset' => $dataset->value, 'path' => $path]);

        $analysis = $this->analyse->handle($dataset, Storage::disk(self::DISK)->path($path), $extension);

        return [
            'token' => $token,
            'dataset' => $dataset->value,
            'filename' => $file->getClientOriginalName(),
            'summary' => $analysis['summary'],
            // Kolom `analysed` sengaja dibuang: layar cuma perlu status dan
            // alasannya, dan nilai mentahnya tidak perlu bolak-balik ke browser.
            'rows' => array_map(
                fn (array $row) => Arr::except($row, 'analysed'),
                array_slice($analysis['rows'], 0, self::PREVIEW_LIMIT),
            ),
            'truncated' => max(0, count($analysis['rows']) - self::PREVIEW_LIMIT),
        ];
    }

    /**
     * Pratinjau yang tidak pernah dikonfirmasi meninggalkan berkas. Dibersihkan
     * di sini (bukan lewat scheduler) supaya tidak ada job yang harus dijaga.
     */
    private function pruneStaleUploads(): void
    {
        $disk = Storage::disk(self::DISK);
        $cutoff = now()->subDay()->getTimestamp();

        foreach ($disk->files('imports') as $file) {
            if ($disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
            }
        }
    }
}
