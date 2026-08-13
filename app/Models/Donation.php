<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan donasi. Bukan SyncModel — lihat komentar di migrationnya.
 */
class Donation extends Model
{
    use HasFactory;

    public const CHANNELS = ['manual', 'paywuz', 'external'];

    /**
     * `recorded` = donatur mengaku sudah transfer manual dan kami percaya
     * begitu saja (tanpa verifikasi); `paid` hanya dipakai kalau ada bukti
     * dari gateway atau dikonfirmasi superadmin.
     */
    public const STATUSES = ['recorded', 'pending', 'paid', 'expired', 'cancelled'];

    protected $fillable = [
        'user_id', 'order_id', 'donor_name', 'donor_email', 'amount', 'message',
        'channel', 'status', 'reference', 'payment_method', 'redirect_url',
        'is_anonymous', 'paid_at', 'raw_response', 'raw_webhook',
    ];

    protected $casts = [
        'amount' => 'integer',
        'is_anonymous' => 'boolean',
        'paid_at' => 'datetime',
        'raw_response' => 'array',
        'raw_webhook' => 'array',
    ];

    /** URL halaman terima kasih memakai order_id, bukan id berurut. */
    public function getRouteKeyName(): string
    {
        return 'order_id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Nama yang boleh tampil di dinding donatur. */
    public function publicName(): string
    {
        return $this->is_anonymous ? 'Hamba Allah' : $this->donor_name;
    }
}
