<?php

namespace App\Actions\Cashflow;

use App\Actions\Sync\WriteEntity;
use App\Models\CashflowCategory;
use App\Models\CashflowEntry;

class SaveEntry
{
    public function __construct(private readonly WriteEntity $writer) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public function handle(array $data, ?CashflowEntry $entry = null): string
    {
        return $this->writer->upsert('cashflow_entries', [
            'category_id' => $data['category_id'] ?? null,
            'session_id' => $entry?->session_id,
            'direction' => $this->direction($data),
            'amount' => (int) $data['amount'],
            'source' => $entry?->source ?? 'manual',
            'source_ref' => $entry?->source_ref,
            'note' => $data['note'] ?? null,
            'occurred_at' => (int) $data['occurred_at'],
        ], $entry?->id);
    }

    /**
     * debit = uang masuk, credit = uang keluar (pos-kacaw/src/db/types.ts).
     * Arahnya diturunkan dari tipe kategori supaya tidak pernah bertentangan
     * dengan kategorinya — persis seperti klien.
     */
    private function direction(array $data): string
    {
        $categoryId = $data['category_id'] ?? null;

        if ($categoryId !== null) {
            $category = CashflowCategory::query()->find($categoryId);

            if ($category !== null) {
                return $category->type === 'income' ? 'debit' : 'credit';
            }
        }

        return ($data['type'] ?? 'expense') === 'income' ? 'debit' : 'credit';
    }
}
