<?php

namespace App\Actions\ImportExport;

use App\Actions\ImportExport\Contracts\Exporter;
use App\Support\ImportExport\Dataset;
use App\Support\ReportPeriod;
use App\Support\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Satu pintu unduh untuk semua dataset. Exporter-nya di-resolve dari container
 * (bukan `new`) supaya ia tetap bisa menerima dependensi seperti aksi lain.
 */
class ExportDataset
{
    public function handle(Dataset $dataset, string $format, ReportPeriod $period): StreamedResponse
    {
        /** @var Exporter $exporter */
        $exporter = app($dataset->exporter());

        return Spreadsheet::download(
            $format,
            $exporter->filename($period),
            $exporter->headers(),
            $exporter->rows($period),
        );
    }
}
