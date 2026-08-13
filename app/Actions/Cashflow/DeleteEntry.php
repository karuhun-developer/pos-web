<?php

namespace App\Actions\Cashflow;

use App\Actions\Sync\WriteEntity;
use App\Models\CashflowEntry;

class DeleteEntry
{
    public function __construct(private readonly WriteEntity $writer) {}

    public function handle(CashflowEntry $entry): void
    {
        $this->writer->delete('cashflow_entries', $entry->id);
    }
}
