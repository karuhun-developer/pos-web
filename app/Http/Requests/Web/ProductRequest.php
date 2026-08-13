<?php

namespace App\Http\Requests\Web;

use App\Support\StoreContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

class ProductRequest extends FormRequest
{
    /** Otorisasi dilakukan lewat policy di controller (route model binding). */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'category_id' => ['nullable', 'uuid', $this->existsInStore('categories')],
            'sku' => ['nullable', 'string', 'max:60', $this->uniqueInStore('sku')],
            'barcode' => ['nullable', 'string', 'max:60', $this->uniqueInStore('barcode')],
            'barcode_type' => ['nullable', 'string', Rule::in(['EAN13', 'EAN8', 'UPC', 'CODE128', 'CODE39', 'ITF14'])],
            'price' => ['required', 'integer', 'min:0', 'max:999999999'],
            'cost' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'track_stock' => ['boolean'],
            'stock' => ['nullable', 'integer', 'min:-999999', 'max:999999'],
            'active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'], // 4 MB, sebelum base64
            'remove_image' => ['boolean'],
        ];
    }

    /** @return array<string,string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama produk',
            'category_id' => 'kategori',
            'price' => 'harga jual',
            'cost' => 'harga modal',
            'stock' => 'stok',
            'image' => 'gambar',
        ];
    }

    /**
     * Unik per toko, bukan global — dan mengabaikan baris yang sudah jadi
     * tombstone supaya SKU bekas produk terhapus bisa dipakai lagi.
     */
    private function uniqueInStore(string $column): Unique
    {
        return Rule::unique('products', $column)
            ->where(fn ($query) => $query->where('store_id', StoreContext::id())->whereNull('deleted_at'))
            ->ignore($this->route('product')?->id, 'id');
    }

    private function existsInStore(string $table): Exists
    {
        return Rule::exists($table, 'id')
            ->where(fn ($query) => $query->where('store_id', StoreContext::id())->whereNull('deleted_at'));
    }
}
