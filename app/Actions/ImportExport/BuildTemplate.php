<?php

namespace App\Actions\ImportExport;

use App\Support\ImportExport\Dataset;
use App\Support\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Template kosong berisi judul kolom + satu baris contoh. Baris contoh sengaja
 * ikut: berkas yang cuma berisi judul kolom membuat orang menebak-nebak format
 * tanggal dan cara menulis "ya/tidak".
 */
class BuildTemplate
{
    public function __construct(private readonly AnalyseImport $analyse) {}

    public function handle(Dataset $dataset, string $format): StreamedResponse
    {
        $importer = $this->analyse->importer($dataset);

        return Spreadsheet::download(
            $format,
            "template-{$dataset->value}",
            array_keys($importer->columns()),
            [$importer->sample()],
        );
    }
}
