<?php

namespace App\Actions\Catalog;

use App\Actions\Sync\WriteEntity;
use App\Models\Product;

class DeleteProduct
{
    public function __construct(private readonly WriteEntity $writer) {}

    /** Tombstone, bukan hard delete — supaya penghapusan ikut turun ke device. */
    public function handle(Product $product): void
    {
        $this->writer->delete('products', $product->id);
    }
}
