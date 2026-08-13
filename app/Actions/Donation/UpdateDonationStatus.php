<?php

namespace App\Actions\Donation;

use App\Models\Donation;
use App\Models\User;

/**
 * Superadmin menerima atau menolak sebuah donasi. Yang ditinjau bukan uangnya
 * (itu tidak pernah diverifikasi) melainkan nama & pesan yang akan tampil di
 * halaman publik.
 *
 * Jejak peninjau ikut disimpan supaya kalau ada pesan lolos yang seharusnya
 * tidak, ketahuan siapa yang menyetujuinya. Dikembalikan ke `pending` berarti
 * jejaknya ikut dibersihkan — statusnya memang belum ditinjau lagi.
 */
class UpdateDonationStatus
{
    public function handle(Donation $donation, string $status, ?User $reviewer = null): Donation
    {
        $reviewed = $status !== 'pending';

        $donation->update([
            'status' => $status,
            'reviewed_at' => $reviewed ? now() : null,
            'reviewed_by' => $reviewed ? $reviewer?->getKey() : null,
        ]);

        return $donation;
    }
}
