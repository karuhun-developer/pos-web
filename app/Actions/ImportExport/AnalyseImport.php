<?php

namespace App\Actions\ImportExport;

use App\Actions\ImportExport\Contracts\Importer;
use App\Support\ImportExport\Dataset;
use App\Support\Spreadsheet;
use RuntimeException;

/**
 * Membaca berkas dan menilai tiap barisnya. Dipakai DUA KALI: sekali untuk
 * pratinjau, sekali lagi saat konfirmasi — dengan importer yang baru dibuat
 * tiap kali, jadi pratinjau dan hasil akhir dinilai dengan aturan yang sama.
 */
class AnalyseImport
{
    /**
     * Importer-nya ikut dikembalikan karena penerapan WAJIB memakai instance
     * yang sama dengan yang menilai: ia menyimpan indeks & daftar penanda yang
     * sudah muncul, jadi baris kedua dengan SKU yang sama tahu bahwa baris
     * pertama sudah membuat produknya.
     *
     * @return array{rows:list<array<string,mixed>>,summary:array{total:int,new:int,update:int,error:int},importer:Importer}
     */
    public function handle(Dataset $dataset, string $path, string $extension): array
    {
        $importer = $this->importer($dataset);
        $summary = ['total' => 0, 'new' => 0, 'update' => 0, 'error' => 0];
        $rows = [];

        foreach (Spreadsheet::read($path, $extension) as $row) {
            $result = $importer->analyse($row['values']);
            $summary['total']++;
            $summary[$result['status']]++;

            $rows[] = [
                'line' => $row['line'],
                'status' => $result['status'],
                'label' => $result['label'],
                'reason' => $result['reason'],
                'analysed' => $result,
            ];
        }

        return ['rows' => $rows, 'summary' => $summary, 'importer' => $importer];
    }

    public function importer(Dataset $dataset): Importer
    {
        $class = $dataset->importer();

        if ($class === null) {
            throw new RuntimeException("Dataset {$dataset->value} hanya bisa diekspor.");
        }

        return app($class);
    }
}
