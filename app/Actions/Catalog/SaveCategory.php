<?php

namespace App\Actions\Catalog;

use App\Actions\Sync\WriteEntity;
use App\Models\Category;

class SaveCategory
{
    public function __construct(private readonly WriteEntity $writer) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public function handle(array $data, ?Category $category = null): string
    {
        return $this->writer->upsert('categories', [
            'name' => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'color' => $data['color'] ?? null,
        ], $category?->id);
    }
}
