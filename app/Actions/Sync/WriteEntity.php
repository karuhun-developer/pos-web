<?php

namespace App\Actions\Sync;

use App\Models\SyncModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * SATU-SATUNYA pintu tulis entity sync dari sisi server (web, impor, seeder).
 *
 * Kenapa tidak langsung Eloquent? Karena SyncModel punya $timestamps = false
 * dan timestamp-nya epoch ms. Row yang ditulis tanpa updated_at tidak akan
 * pernah ikut terbawa PullChanges (`where updated_at > since`), alias
 * perubahan dari web tidak pernah sampai ke Android. Jadi tulisan web
 * dialirkan lewat ApplyChange yang sama dengan push dari device — gratis
 * dapat LWW, tombstone, pengelolaan sync_version, pemilihan kolom, dan
 * konversi media base64 → storage.
 */
class WriteEntity
{
    public function __construct(private readonly ApplyChange $apply) {}

    /**
     * Buat/ubah satu row. Kembalikan id-nya (UUID yang di-generate di sini
     * kalau row baru).
     *
     * @param  array<string,mixed>  $attributes
     */
    public function upsert(string $entity, array $attributes, ?string $id = null): string
    {
        $modelClass = $this->modelClass($entity);
        $id ??= (string) Str::uuid();

        $existing = $modelClass::query()->find($id);
        $timestamp = $this->timestampFor($existing);

        $payload = [
            ...$attributes,
            'id' => $id,
            'updated_at' => $timestamp,
        ];

        // created_at hanya ditulis saat row lahir; kalau tidak dikirim,
        // ApplyChange::pickColumns() membiarkan nilai lama apa adanya.
        if ($existing === null) {
            $payload['created_at'] = $attributes['created_at'] ?? $timestamp;
            $payload['deleted_at'] = null;
        }

        $this->apply->handle([
            'id' => 'web-'.Str::uuid(),
            'entity' => $entity,
            'entityId' => $id,
            'op' => $existing === null ? 'insert' : 'update',
            'payload' => $payload,
        ], $this->originDevice());

        return $id;
    }

    /**
     * Hapus = TOMBSTONE, bukan hard delete. Row-nya tetap ada dengan
     * deleted_at terisi supaya penghapusan ikut tersinkron ke device.
     */
    public function delete(string $entity, string $id): void
    {
        $modelClass = $this->modelClass($entity);
        $existing = $modelClass::query()->find($id);
        $timestamp = $this->timestampFor($existing);

        $this->apply->handle([
            'id' => 'web-'.Str::uuid(),
            'entity' => $entity,
            'entityId' => $id,
            'op' => 'delete',
            'payload' => ['id' => $id, 'deleted_at' => $timestamp, 'updated_at' => $timestamp],
        ], $this->originDevice());
    }

    /**
     * Epoch ms sekarang, tapi selalu lebih baru dari row yang ada. Jam device
     * bisa meleset ke depan; tanpa clamp ini, edit dari web akan ditolak
     * ApplyChange sebagai `stale` sampai jam dunia nyata menyusul.
     */
    private function timestampFor(?SyncModel $existing): int
    {
        $now = (int) round(microtime(true) * 1000);

        return $existing === null ? $now : max($now, (int) $existing->updated_at + 1);
    }

    /** Jejak asal perubahan; membedakan tulisan web dari push device. */
    private function originDevice(): string
    {
        $id = Auth::id();

        return $id === null ? 'web' : 'web:'.$id;
    }

    /** @return class-string<SyncModel> */
    private function modelClass(string $entity): string
    {
        $modelClass = config("sync.entities.$entity");

        if ($modelClass === null) {
            throw new InvalidArgumentException("Entity sync tidak dikenal: {$entity}");
        }

        return $modelClass;
    }
}
