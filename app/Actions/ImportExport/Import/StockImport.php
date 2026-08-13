<?php

namespace App\Actions\ImportExport\Import;

use App\Actions\ImportExport\Contracts\Importer;
use App\Actions\ImportExport\Import\Concerns\ParsesValues;
use App\Actions\Sync\WriteEntity;
use App\Models\Product;

/**
 * Stok opname: menyetel sisa stok ke hasil hitungan fisik.
 *
 * Hanya kolom stock yang ditulis — harga, nama, dan kategori tidak ikut
 * tersentuh meskipun ada di berkas hasil ekspor. Baris tanpa `stok_fisik`
 * dilewati (bukan error): lembar opname memang biasanya diisi sebagian.
 */
class StockImport implements Importer
{
    use ParsesValues;

    /** @var array<string,array{id:string,name:string,stock:int}>|null */
    private ?array $index = null;

    public function __construct(private readonly WriteEntity $writer) {}

    public function columns(): array
    {
        return [
            'sku' => 'Kode produk',
            'barcode' => 'Dipakai kalau SKU kosong',
            'stok_fisik' => 'Hasil hitungan; kosongkan untuk melewati baris',
        ];
    }

    public function sample(): array
    {
        return ['KOPI-01', '8991002101234', '23'];
    }

    public function analyse(array $values): array
    {
        $key = mb_strtolower($this->text($values, 'sku') ?? $this->text($values, 'barcode') ?? '');
        $label = $this->text($values, 'nama') ?? $key;

        if ($key === '') {
            return $this->error($label ?: '(tanpa penanda)', 'SKU atau barcode harus diisi.');
        }

        $product = $this->index()[$key] ?? null;

        if ($product === null) {
            return $this->error($label, 'Produk dengan penanda ini tidak ditemukan.');
        }

        $counted = $this->number($values, 'stok_fisik');

        if ($counted === null) {
            return $this->error($product['name'], 'Kolom stok_fisik belum diisi — baris ini dilewati.');
        }

        return [
            'status' => 'update',
            'reason' => $counted === $product['stock']
                ? 'Sama dengan stok sistem.'
                : sprintf('%+d dari %d', $counted - $product['stock'], $product['stock']),
            'label' => $product['name'],
            'id' => $product['id'],
            'attributes' => ['stock' => $counted],
        ];
    }

    public function apply(array $analysed): void
    {
        $this->writer->upsert('products', $analysed['attributes'], $analysed['id']);
    }

    /** @return array<string,array{id:string,name:string,stock:int}> */
    private function index(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }

        $this->index = [];

        Product::query()
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'sku', 'barcode', 'stock'])
            ->each(function (Product $product) {
                foreach ([$product->sku, $product->barcode] as $value) {
                    if ($value !== null && $value !== '') {
                        $this->index[mb_strtolower($value)] ??= [
                            'id' => $product->id,
                            'name' => $product->name,
                            'stock' => (int) $product->stock,
                        ];
                    }
                }
            });

        return $this->index;
    }
}
