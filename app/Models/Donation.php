<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan donasi. Bukan SyncModel — lihat komentar di migrationnya.
 */
class Donation extends Model
{
    use HasFactory;

    /** Cara donatur mengaku sudah mengirim uangnya. */
    public const CHANNELS = ['qris', 'transfer', 'saweria'];

    /**
     * Tidak ada verifikasi pembayaran di sini — yang dimoderasi adalah
     * tampilnya nama & pesan di halaman publik. `pending` = baru masuk,
     * `approved` = boleh tampil di dinding donatur dan ikut dihitung sebagai
     * terkumpul, `rejected` = spam, disimpan tapi tidak pernah tampil.
     */
    public const STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = [
        'user_id', 'order_id', 'donor_name', 'donor_email', 'amount', 'message',
        'channel', 'status', 'is_anonymous', 'reviewed_at', 'reviewed_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'is_anonymous' => 'boolean',
        'reviewed_at' => 'datetime',
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

    /** Superadmin yang menerima atau menolak donasi ini. */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Yang boleh dilihat publik. Dipakai dinding donatur dan angka
     * "terkumpul" — keduanya harus bercerita hal yang sama.
     *
     * @param  Builder<Donation>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', 'approved');
    }

    /** Nama yang boleh tampil di dinding donatur. */
    public function publicName(): string
    {
        return $this->is_anonymous ? 'Hamba Allah' : $this->donor_name;
    }
}
