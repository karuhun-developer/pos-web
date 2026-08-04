<?php

namespace App\Models;

use App\Models\Scopes\StoreScope;
use App\Observers\SyncObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[ScopedBy([StoreScope::class])]
#[ObservedBy([SyncObserver::class])]
class SaleItem extends SyncModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'price_snapshot' => 'integer',
            'qty' => 'integer',
            'discount' => 'integer',
            'line_total' => 'integer',
        ]);
    }
}
