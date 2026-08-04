<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Basis semua entity yang disync. PK = UUID string yang di-generate CLIENT
 * (bukan auto-increment, bukan HasUuids). Timestamp created_at/updated_at =
 * epoch ms integer yang dikelola oleh mekanisme sync — bukan timestamp Eloquent,
 * jadi $timestamps = false.
 *
 * Tiap child WAJIB menempelkan atribut tenant + observer sendiri:
 *   #[ScopedBy([StoreScope::class])]
 *   #[ObservedBy([SyncObserver::class])]
 * (atribut PHP tidak diwariskan ke reflection kelas anak).
 */
abstract class SyncModel extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    /** Cast kolom sync universal; child menambah cast domainnya via array_merge. */
    protected function casts(): array
    {
        return [
            'created_at' => 'integer',
            'updated_at' => 'integer',
            'deleted_at' => 'integer',
            'sync_version' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
