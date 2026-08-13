<?php

namespace App\Policies;

use App\Models\Donation;
use App\Models\User;

/**
 * Donasi adalah data platform, bukan milik toko — jadi tidak memakai
 * ChecksStoreOwnership. Yang boleh melihat & mengubahnya hanya pemegang
 * permission platform `donation.manage` (praktisnya: superadmin, yang bahkan
 * sudah lolos lebih dulu lewat Gate::before).
 */
class DonationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('donation.manage');
    }

    public function view(User $user, Donation $donation): bool
    {
        return $user->can('donation.manage');
    }

    public function update(User $user, Donation $donation): bool
    {
        return $user->can('donation.manage');
    }

    /**
     * Mengatur cara berdonasi (QRIS, rekening, Saweria). Tidak menempel pada
     * satu baris donasi mana pun, jadi abilitynya tingkat kelas.
     */
    public function manage(User $user): bool
    {
        return $user->can('donation.manage');
    }
}
