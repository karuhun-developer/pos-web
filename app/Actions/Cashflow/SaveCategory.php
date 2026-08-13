<?php

namespace App\Actions\Cashflow;

use App\Actions\Sync\WriteEntity;
use App\Models\CashflowCategory;

class SaveCategory
{
    public function __construct(private readonly WriteEntity $writer) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public function handle(array $data, ?CashflowCategory $category = null): string
    {
        return $this->writer->upsert('cashflow_categories', [
            'name' => $data['name'],
            // Tipe kategori sistem (mis. "Penjualan") tidak boleh berubah —
            // ledger dari checkout bergantung padanya.
            'type' => $category?->is_system ? $category->type : $data['type'],
            'is_system' => (int) ($category?->is_system ?? 0),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ], $category?->id);
    }
}
