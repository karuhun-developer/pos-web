<?php

namespace App\Actions\Catalog;

use App\Actions\Sync\WriteEntity;
use App\Models\Category;

class DeleteCategory
{
    public function __construct(private readonly WriteEntity $writer) {}

    /**
     * Produk yang memakai kategori ini TIDAK ikut diubah — sama seperti klien
     * (pos-kacaw/src/stores/categories.ts hanya soft-delete), dan tampilannya
     * jatuh ke "Tanpa kategori". Menulis ulang seluruh produk hanya untuk
     * mengosongkan category_id akan membuat delta pull membengkak tanpa guna.
     */
    public function handle(Category $category): void
    {
        $this->writer->delete('categories', $category->id);
    }
}
