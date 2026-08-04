<?php

namespace App\Actions\Sync;

use App\Models\SyncModel;

/**
 * Menarik perubahan satu entity untuk toko aktif: semua row updated_at > since
 * (termasuk tombstone), diurutkan updated_at ASC. cursor = updated_at terbesar.
 * Lihat kontrak: docs/api-contract.md §3.
 */
class PullChanges
{
    /**
     * @return array{entity:string,changes:list<array<string,mixed>>,cursor:int}
     */
    public function handle(string $entity, int $since): array
    {
        /** @var class-string<SyncModel> $modelClass */
        $modelClass = config("sync.entities.$entity");

        $rows = $modelClass::query()
            ->where('updated_at', '>', $since)
            ->orderBy('updated_at')
            ->get();

        $changes = $rows->map(fn (SyncModel $row) => $this->toPayload($row, $entity))->all();
        $cursor = (int) ($rows->max('updated_at') ?? $since);

        return ['entity' => $entity, 'changes' => $changes, 'cursor' => $cursor];
    }

    /** Bentuk row server → payload yang cocok skema FE (SyncEntity). */
    private function toPayload(SyncModel $row, string $entity): array
    {
        $attrs = $row->attributesToArray();

        // Kolom server-only tidak dibocorkan ke client.
        unset($attrs['store_id'], $attrs['origin_device']);

        // Kolom lokal-FE dikembalikan default agar bentuk konsisten.
        $attrs['dirty'] = 0;
        $attrs['remote_id'] = null;

        // Media: byte tidak dikirim inline; FE muat dari remote_url.
        if ($entity === 'media') {
            $attrs['data'] = null;
        }

        return $attrs;
    }
}
