<?php

namespace App\Actions\Catalog;

use App\Actions\Sync\WriteEntity;
use App\Models\Product;
use Illuminate\Http\UploadedFile;

/**
 * Simpan produk (baru atau ubah). Semua tulisan lewat WriteEntity supaya
 * updated_at epoch-ms terisi dan barisnya ikut ter-pull perangkat Android.
 */
class SaveProduct
{
    public function __construct(
        private readonly WriteEntity $writer,
        private readonly StoreProductImage $image,
    ) {}

    /**
     * @param  array<string,mixed>  $data  hasil validasi ProductRequest
     * @return string id produk
     */
    public function handle(array $data, ?Product $product = null): string
    {
        $attributes = [
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'barcode_type' => $data['barcode_type'] ?? 'EAN13',
            'price' => (int) $data['price'],
            'cost' => (int) ($data['cost'] ?? 0),
            'track_stock' => (int) ($data['track_stock'] ?? 0),
            'stock' => (int) ($data['stock'] ?? 0),
            'active' => (int) ($data['active'] ?? 1),
        ];

        $attributes['image_path'] = $this->resolveImage($data, $product);

        return $this->writer->upsert('products', $attributes, $product?->id);
    }

    /**
     * Tiga kemungkinan: unggah gambar baru, minta hapus gambar, atau biarkan
     * apa adanya. Yang terakhir tetap harus dikirim ulang karena ApplyChange
     * menulis seluruh kolom yang ada di payload.
     */
    private function resolveImage(array $data, ?Product $product): ?string
    {
        $upload = $data['image'] ?? null;

        if ($upload instanceof UploadedFile) {
            return $this->image->handle($upload);
        }

        if (! empty($data['remove_image'])) {
            return null;
        }

        return $product?->image_path;
    }
}
