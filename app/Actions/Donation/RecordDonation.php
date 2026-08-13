<?php

namespace App\Actions\Donation;

use App\Models\Donation;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Membuat baris donasi. Pembayarannya tidak diverifikasi — donatur transfer
 * sendiri lalu mencatatkannya di sini. Yang ditinjau superadmin adalah nama &
 * pesannya, jadi baris baru selalu lahir `pending`.
 */
class RecordDonation
{
    /**
     * @param  array<string,mixed>  $attributes
     */
    public function handle(array $attributes, ?User $user = null, string $status = 'pending'): Donation
    {
        return Donation::create([
            'user_id' => $user?->getKey(),
            'order_id' => self::orderId(),
            'donor_name' => $attributes['donor_name'],
            'donor_email' => $attributes['donor_email'] ?? $user?->email,
            'amount' => (int) $attributes['amount'],
            'message' => $attributes['message'] ?? null,
            'channel' => $attributes['channel'],
            'status' => $status,
            'is_anonymous' => (bool) ($attributes['is_anonymous'] ?? false),
        ]);
    }

    /**
     * Order id sekaligus jadi route key halaman terima kasih, jadi bagian
     * acaknya harus cukup panjang untuk tidak bisa ditebak — id berurut akan
     * membuat pesan donatur lain bisa diintip dengan menaikkan angka.
     */
    public static function orderId(): string
    {
        return 'DON-'.now()->format('ymd').'-'.Str::lower(Str::random(12));
    }
}
