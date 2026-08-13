<?php

namespace App\Actions\ImportExport\Import;

use App\Actions\ImportExport\Contracts\Importer;
use App\Actions\ImportExport\Import\Concerns\ParsesValues;
use App\Actions\Sync\WriteEntity;
use App\Models\Category;
use App\Models\Product;

/**
 * Impor produk. Pencocokan berjenjang: SKU dulu, baru barcode — SKU adalah
 * kunci yang dikelola toko sendiri, sedangkan barcode bisa dipakai bersama
 * beberapa varian dari pabrik.
 *
 * Baris yang kategorinya tidak dikenal DITOLAK, bukan diam-diam membuat
 * kategori baru: salah ketik satu huruf akan melahirkan kategori kembar yang
 * baru ketahuan berminggu-minggu kemudian.
 */
class ProductImport implements Importer
{
    use ParsesValues;

    /** @var array<string,string>|null sku/barcode (lowercase) => product id */
    private ?array $index = null;

    /** @var array<string,string>|null nama kategori (lowercase) => id */
    private ?array $categories = null;

    /** @var array<string,true> kunci yang sudah muncul di berkas yang sama */
    private array $seen = [];

    public function __construct(private readonly WriteEntity $writer) {}

    public function columns(): array
    {
        return [
            'sku' => 'Kode produk; dipakai mencocokkan baris dengan produk yang ada',
            'barcode' => 'Pencocokan cadangan kalau SKU kosong',
            'nama' => 'Wajib untuk produk baru',
            'kategori' => 'Nama kategori yang sudah ada',
            'harga' => 'Harga jual, rupiah bulat',
            'modal' => 'Harga modal, rupiah bulat',
            'lacak_stok' => 'ya / tidak',
            'stok' => 'Sisa stok saat ini',
            'aktif' => 'ya / tidak',
        ];
    }

    public function sample(): array
    {
        return ['KOPI-01', '8991002101234', 'Kopi Susu', 'Minuman', '18000', '9000', 'ya', '25', 'ya'];
    }

    public function analyse(array $values): array
    {
        $sku = $this->text($values, 'sku');
        $barcode = $this->text($values, 'barcode');
        $name = $this->text($values, 'nama');
        $key = mb_strtolower($sku ?? $barcode ?? '');

        if ($key === '') {
            return $this->error($name ?? '(tanpa nama)', 'SKU atau barcode harus diisi sebagai penanda baris.');
        }

        if (isset($this->seen[$key])) {
            return $this->error($name ?? $key, 'Penanda ini muncul lebih dari sekali di berkas.');
        }

        $this->seen[$key] = true;

        $existingId = $this->index()[$key] ?? null;
        $label = $name ?? $key;

        if ($existingId === null && $name === null) {
            return $this->error($key, 'Produk baru wajib punya nama.');
        }

        $price = $this->number($values, 'harga');
        if ($price === null && $existingId === null) {
            return $this->error($label, 'Produk baru wajib punya harga.');
        }

        if ($price !== null && $price < 0) {
            return $this->error($label, 'Harga tidak boleh negatif.');
        }

        $categoryName = $this->text($values, 'kategori');
        $categoryId = null;

        if ($categoryName !== null) {
            $categoryId = $this->categories()[mb_strtolower($categoryName)] ?? null;

            if ($categoryId === null) {
                return $this->error($label, "Kategori \"{$categoryName}\" belum ada. Impor kategori dulu.");
            }
        }

        // Kolom yang dikosongkan pada baris update sengaja TIDAK ikut dikirim:
        // ApplyChange hanya menulis kolom yang ada di payload, jadi gambar dan
        // kolom lain yang tidak ada di berkas tetap utuh.
        $attributes = array_filter([
            'name' => $name,
            'sku' => $sku,
            'barcode' => $barcode,
            'category_id' => $categoryId,
            'price' => $price,
            'cost' => $this->number($values, 'modal'),
            'stock' => $this->number($values, 'stok'),
        ], fn ($value) => $value !== null);

        if (array_key_exists('lacak_stok', $values)) {
            $attributes['track_stock'] = (int) $this->flag($values, 'lacak_stok', true);
        }

        if (array_key_exists('aktif', $values)) {
            $attributes['active'] = (int) $this->flag($values, 'aktif', true);
        }

        return [
            'status' => $existingId === null ? 'new' : 'update',
            'reason' => null,
            'label' => $label,
            'id' => $existingId,
            'attributes' => $attributes,
        ];
    }

    public function apply(array $analysed): void
    {
        $id = $this->writer->upsert('products', $analysed['attributes'], $analysed['id']);

        // Baris berikutnya dengan SKU sama harus terbaca sebagai update, bukan
        // produk baru kedua.
        foreach (['sku', 'barcode'] as $column) {
            $value = $analysed['attributes'][$column] ?? null;

            if ($value !== null) {
                $this->index[mb_strtolower((string) $value)] = $id;
            }
        }
    }

    /** @return array<string,string> */
    private function index(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }

        $this->index = [];

        Product::query()
            ->whereNull('deleted_at')
            ->get(['id', 'sku', 'barcode'])
            ->each(function (Product $product) {
                foreach ([$product->sku, $product->barcode] as $value) {
                    if ($value !== null && $value !== '') {
                        $this->index[mb_strtolower($value)] ??= $product->id;
                    }
                }
            });

        return $this->index;
    }

    /** @return array<string,string> */
    private function categories(): array
    {
        return $this->categories ??= Category::query()
            ->whereNull('deleted_at')
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Category $category) => [mb_strtolower($category->name) => $category->id])
            ->all();
    }
}
