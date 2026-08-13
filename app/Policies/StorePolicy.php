<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

/**
 * Store bukan entity sync (tidak punya store_id), jadi tidak memakai
 * ChecksStoreOwnership. Kepemilikannya dibaca dari pivot store_user.
 */
class StorePolicy
{
    /** Boleh melihat/berpindah ke toko yang dia jadi anggotanya. */
    public function view(User $user, Store $store): bool
    {
        return $this->isMember($user, $store);
    }

    /** Ganti nama & pengaturan toko: pemilik saja. */
    public function update(User $user, Store $store): bool
    {
        return (int) $store->owner_id === $user->getKey();
    }

    /** Kelola anggota (undang/keluarkan): pemilik saja. */
    public function manageMembers(User $user, Store $store): bool
    {
        return $this->update($user, $store);
    }

    private function isMember(User $user, Store $store): bool
    {
        return $user->stores()->whereKey($store->getKey())->exists();
    }
}
