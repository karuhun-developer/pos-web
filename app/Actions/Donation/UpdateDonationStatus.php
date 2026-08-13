<?php

namespace App\Actions\Donation;

use App\Models\Donation;

/**
 * Superadmin mengubah status donasi secara manual — dipakai untuk transfer
 * manual yang sudah dicek mutasinya, atau membatalkan catatan iseng.
 *
 * paid_at ikut diurus di sini supaya tidak ada donasi berstatus `paid` tanpa
 * tanggal, atau sebaliknya.
 */
class UpdateDonationStatus
{
    public function handle(Donation $donation, string $status): Donation
    {
        $donation->update([
            'status' => $status,
            'paid_at' => $status === 'paid' ? ($donation->paid_at ?? now()) : null,
        ]);

        return $donation;
    }
}
