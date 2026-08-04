<?php

namespace App\Observers;

use App\Models\SyncModel;
use App\Support\StoreContext;

/**
 * Menjaga invarian tenant: isi store_id dari toko aktif saat row dibuat
 * (payload client tidak menentukan tenant). Dipasang lewat atribut
 * #[ObservedBy] di tiap model sync.
 */
class SyncObserver
{
    public function creating(SyncModel $model): void
    {
        if (empty($model->store_id) && StoreContext::has()) {
            $model->store_id = StoreContext::id();
        }
    }
}
