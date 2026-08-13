<?php

namespace App\Actions\ImportExport\Import;

use App\Actions\ImportExport\Contracts\Importer;
use App\Actions\ImportExport\Import\Concerns\ParsesValues;
use App\Actions\Sync\WriteEntity;
use App\Models\Category;

/**
 * Kategori dicocokkan berdasarkan nama (tanpa peduli besar-kecil huruf) karena
 * itulah satu-satunya penanda yang dipegang user — id-nya UUID buatan mesin.
 */
class CategoryImport implements Importer
{
    use ParsesValues;

    /** @var array<string,string>|null nama (lowercase) => id */
    private ?array $index = null;

    /** @var array<string,true> */
    private array $seen = [];

    public function __construct(private readonly WriteEntity $writer) {}

    public function columns(): array
    {
        return [
            'nama' => 'Nama kategori; dipakai mencocokkan baris',
            'warna' => 'Kode hex, mis. #2a78d6 (boleh kosong)',
            'urutan' => 'Angka urut tampil, kecil = di atas',
        ];
    }

    public function sample(): array
    {
        return ['Minuman', '#2a78d6', '1'];
    }

    public function analyse(array $values): array
    {
        $name = $this->text($values, 'nama');

        if ($name === null) {
            return $this->error('(tanpa nama)', 'Nama kategori wajib diisi.');
        }

        $key = mb_strtolower($name);

        if (isset($this->seen[$key])) {
            return $this->error($name, 'Nama ini muncul lebih dari sekali di berkas.');
        }

        $this->seen[$key] = true;

        $color = $this->text($values, 'warna');

        if ($color !== null && preg_match('/^#[0-9a-fA-F]{6}$/', $color) !== 1) {
            return $this->error($name, 'Warna harus berupa hex 6 digit, mis. #2a78d6.');
        }

        $existingId = $this->index()[$key] ?? null;

        $attributes = array_filter([
            'name' => $name,
            'color' => $color,
            'sort_order' => $this->number($values, 'urutan'),
        ], fn ($value) => $value !== null);

        return [
            'status' => $existingId === null ? 'new' : 'update',
            'reason' => null,
            'label' => $name,
            'id' => $existingId,
            'attributes' => $attributes,
        ];
    }

    public function apply(array $analysed): void
    {
        $id = $this->writer->upsert('categories', $analysed['attributes'], $analysed['id']);

        $this->index[mb_strtolower((string) $analysed['attributes']['name'])] = $id;
    }

    /** @return array<string,string> */
    private function index(): array
    {
        return $this->index ??= Category::query()
            ->whereNull('deleted_at')
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Category $category) => [mb_strtolower($category->name) => $category->id])
            ->all();
    }
}
