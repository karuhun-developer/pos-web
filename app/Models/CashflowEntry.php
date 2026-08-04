<?php

namespace App\Models;

use App\Models\Scopes\StoreScope;
use App\Observers\SyncObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[ScopedBy([StoreScope::class])]
#[ObservedBy([SyncObserver::class])]
class CashflowEntry extends SyncModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'amount' => 'integer',
            'occurred_at' => 'integer',
        ]);
    }
}
