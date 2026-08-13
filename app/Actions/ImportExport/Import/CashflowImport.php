<?php

namespace App\Actions\ImportExport\Import;

use App\Actions\ImportExport\Contracts\Importer;
use App\Actions\ImportExport\Import\Concerns\ParsesValues;
use App\Actions\Sync\WriteEntity;
use App\Models\CashflowCategory;

/**
 * Catatan kas tidak punya kunci alami — dua pengeluaran Rp 50.000 di hari yang
 * sama pada kategori yang sama memang bisa benar-benar dua kejadian. Jadi
 * SEMUA baris masuk sebagai catatan baru; mengunggah berkas yang sama dua kali
 * akan menggandakan datanya, dan pratinjau menyatakan itu terang-terangan.
 */
class CashflowImport implements Importer
{
    use ParsesValues;

    /** @var array<string,array{id:string,type:string}>|null nama (lowercase) => kategori */
    private ?array $categories = null;

    public function __construct(private readonly WriteEntity $writer) {}

    public function columns(): array
    {
        return [
            'tanggal' => 'Format 2026-08-13 atau 13/08/2026',
            'jenis' => 'masuk / keluar',
            'kategori' => 'Nama kategori kas yang sudah ada (boleh kosong)',
            'jumlah' => 'Nominal rupiah, selalu positif',
            'catatan' => 'Keterangan bebas',
        ];
    }

    public function sample(): array
    {
        return ['2026-08-13', 'keluar', 'Operasional', '75000', 'Beli galon'];
    }

    public function analyse(array $values): array
    {
        $occurredAt = $this->dayStart($values, 'tanggal');
        $note = $this->text($values, 'catatan');
        $label = $note ?? ($this->text($values, 'kategori') ?? 'Catatan kas');

        if ($occurredAt === null) {
            return $this->error($label, 'Tanggal kosong atau formatnya tidak dikenali.');
        }

        $amount = $this->number($values, 'jumlah');

        if ($amount === null || $amount <= 0) {
            return $this->error($label, 'Jumlah harus angka lebih dari nol.');
        }

        $categoryName = $this->text($values, 'kategori');
        $category = null;

        if ($categoryName !== null) {
            $category = $this->categories()[mb_strtolower($categoryName)] ?? null;

            if ($category === null) {
                return $this->error($label, "Kategori kas \"{$categoryName}\" belum ada.");
            }
        }

        $jenis = mb_strtolower($this->text($values, 'jenis') ?? '');

        if ($category === null && ! in_array($jenis, ['masuk', 'keluar'], true)) {
            return $this->error($label, 'Isi kolom jenis dengan "masuk" atau "keluar", atau pilih kategori.');
        }

        // Arah mengikuti tipe kategori kalau ada — supaya catatan tidak pernah
        // bertentangan dengan kategorinya sendiri (aturan yang sama dipakai
        // form web dan aplikasi kasir).
        $direction = $category !== null
            ? ($category['type'] === 'income' ? 'debit' : 'credit')
            : ($jenis === 'masuk' ? 'debit' : 'credit');

        return [
            'status' => 'new',
            'reason' => null,
            'label' => $label,
            'id' => null,
            'attributes' => [
                'category_id' => $category['id'] ?? null,
                'session_id' => null,
                'direction' => $direction,
                'amount' => $amount,
                'source' => 'manual',
                'source_ref' => null,
                'note' => $note,
                'occurred_at' => $occurredAt,
            ],
        ];
    }

    public function apply(array $analysed): void
    {
        $this->writer->upsert('cashflow_entries', $analysed['attributes']);
    }

    /** @return array<string,array{id:string,type:string}> */
    private function categories(): array
    {
        return $this->categories ??= CashflowCategory::query()
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'type'])
            ->mapWithKeys(fn (CashflowCategory $category) => [
                mb_strtolower($category->name) => ['id' => $category->id, 'type' => $category->type],
            ])
            ->all();
    }
}
