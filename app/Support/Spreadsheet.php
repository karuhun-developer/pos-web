<?php

namespace App\Support;

use Illuminate\Support\Facades\Response;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pembungkus tipis openspout. Semua ekspor ditulis LANGSUNG ke php://output
 * baris demi baris — tidak ada array besar yang dikumpulkan dulu di memori,
 * jadi mengekspor 100 ribu transaksi tidak lebih berat daripada 100.
 */
final class Spreadsheet
{
    public const FORMATS = ['csv', 'xlsx'];

    /**
     * @param  list<string>  $headers
     * @param  iterable<int,list<null|bool|float|int|string>>  $rows
     */
    public static function download(string $format, string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $format = in_array($format, self::FORMATS, true) ? $format : 'csv';
        $writer = self::writer($format);

        return Response::streamDownload(function () use ($writer, $headers, $rows) {
            // php://output aman untuk XLSX: openspout merakit zip-nya di
            // direktori sementara lalu menyalinnya ke pointer ini saat close().
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($headers));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues($row));
            }

            $writer->close();
        }, "{$filename}.{$format}", [
            'Content-Type' => $format === 'xlsx'
                ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                : 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Baca berkas jadi baris asosiatif berkunci header (header dinormalisasi
     * jadi huruf kecil tanpa spasi, jadi "Harga Jual" == "harga_jual").
     *
     * @return list<array{line:int,values:array<string,string>}>
     */
    public static function read(string $path, string $extension): array
    {
        $reader = self::reader($extension);
        $reader->open($path);

        $headers = [];
        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $index => $row) {
                $cells = array_map(
                    fn ($value) => is_string($value) ? trim($value) : (string) $value,
                    $row->toArray(),
                );

                if ($headers === []) {
                    $headers = array_map(self::normalize(...), $cells);

                    continue;
                }

                // Baris kosong di ujung berkas (sering muncul dari Excel)
                // dilewati diam-diam, bukan dilaporkan sebagai error.
                if (implode('', $cells) === '') {
                    continue;
                }

                $values = [];
                foreach ($headers as $position => $header) {
                    if ($header !== '') {
                        $values[$header] = $cells[$position] ?? '';
                    }
                }

                $rows[] = ['line' => $index, 'values' => $values];
            }

            break; // Hanya sheet pertama yang dibaca.
        }

        $reader->close();

        return $rows;
    }

    private static function normalize(string $header): string
    {
        return str_replace(' ', '_', mb_strtolower(trim($header)));
    }

    private static function writer(string $format): WriterInterface
    {
        return $format === 'xlsx' ? new XlsxWriter : new CsvWriter;
    }

    private static function reader(string $extension): ReaderInterface
    {
        return mb_strtolower($extension) === 'xlsx' ? new XlsxReader : new CsvReader;
    }
}
