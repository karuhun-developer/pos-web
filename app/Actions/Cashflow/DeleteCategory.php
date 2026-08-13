<?php

namespace App\Actions\Cashflow;

use App\Actions\Sync\WriteEntity;
use App\Models\CashflowCategory;
use Illuminate\Validation\ValidationException;

class DeleteCategory
{
    public function __construct(private readonly WriteEntity $writer) {}

    public function handle(CashflowCategory $category): void
    {
        if ($category->is_system) {
            throw ValidationException::withMessages([
                'category' => 'Kategori bawaan sistem tidak bisa dihapus.',
            ]);
        }

        $this->writer->delete('cashflow_categories', $category->id);
    }
}
