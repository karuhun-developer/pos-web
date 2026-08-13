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

    public const CHANNELS = ['manual', 'external'];

    /**
     * `recorded` = donatur mengaku sudah transfer dan kami percaya begitu saja
     * (tanpa verifikasi); `paid` dipakai kalau superadmin sudah mencocokkan
     * mutasi rekening.
     */
    public const STATUSES = ['recorded', 'paid', 'cancelled'];

    protected $fillable = [
        'user_id', 'order_id', 'donor_name', 'donor_email', 'amount', 'message',
        'channel', 'status', 'is_anonymous', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'is_anonymous' => 'boolean',
        'paid_at' => 'datetime',
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
