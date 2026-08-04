<?php

namespace App\Models;

use App\Models\Scopes\StoreScope;
use App\Observers\SyncObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[ScopedBy([StoreScope::class])]
#[ObservedBy([SyncObserver::class])]
class CashierSession extends SyncModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'opened_at' => 'integer',
            'closed_at' => 'integer',
            'opening_cash' => 'integer',
            'expected_cash' => 'integer',
            'counted_cash' => 'integer',
            'difference' => 'integer',
        ]);
    }
}
