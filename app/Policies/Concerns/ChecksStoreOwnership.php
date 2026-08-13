<?php

namespace App\Policies\Concerns;

use App\Models\SyncModel;
use App\Models\User;
use App\Support\StoreContext;

/**
 * Aturan kepemilikan data untuk semua entity sync.
 *
 * StoreScope sudah membuat row toko lain "tidak terlihat" (route model binding
 * jadi 404), tapi itu bergantung pada StoreContext yang aktif — dan
 * StoreScope-nya fail-open kalau tidak ada toko aktif. Policy ini lapisan
 * kedua yang memeriksa kepemilikan secara eksplisit, jadi kalau suatu saat ada
 * route yang lupa memasang middleware 'store', jawabannya tetap "tidak boleh"
 * dan bukan "boleh semuanya".
 *
 * Superadmin tidak pernah sampai ke sini: Gate::before di AppServiceProvider
 * sudah meloloskannya lebih dulu.
 */
trait ChecksStoreOwnership
{
    /** Row ini milik toko aktif DAN user memang anggota toko itu. */
    protected function ownsRow(User $user, SyncModel $model): bool
    {
        if (! StoreContext::has()) {
            return false;
        }

        return (int) $model->store_id === StoreContext::id()
            && $user->stores()->whereKey(StoreContext::id())->exists();
    }

    /** Anggota toko aktif (tanpa melihat row tertentu) — untuk viewAny/create. */
    protected function memberOfCurrentStore(User $user): bool
    {
        return StoreContext::has()
            && $user->stores()->whereKey(StoreContext::id())->exists();
    }
}
