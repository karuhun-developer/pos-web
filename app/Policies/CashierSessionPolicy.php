<?php

namespace App\Policies;

use App\Models\CashierSession;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreOwnership;

/**
 * Sesi kasir dibuka & ditutup di perangkat kasir, jadi web hanya membaca
 * riwayatnya — tidak ada create/update/delete di sini.
 */
class CashierSessionPolicy
{
    use ChecksStoreOwnership;

    public function viewAny(User $user): bool
    {
        return $this->memberOfCurrentStore($user);
    }

    public function view(User $user, CashierSession $session): bool
    {
        return $this->ownsRow($user, $session);
    }
}
