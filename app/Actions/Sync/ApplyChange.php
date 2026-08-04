<?php

namespace App\Actions\Sync;

use App\Models\SyncModel;
use App\Sync\RejectReason;
use App\Sync\SyncRejection;
use Illuminate\Support\Facades\Schema;

/**
 * Meng-apply SATU ChangeEnvelope ke DB (dalam scope toko aktif). Aturan:
 * upsert-by-id, Last-Write-Wins by updated_at, delete = tombstone, media base64
 * → storage. Melempar SyncRejection kalau tidak bisa di-apply.
 * Lihat kontrak: docs/api-contract.md §3.4.
 */
class ApplyChange
{
    /** @var array<string,list<string>> cache kolom per tabel */
    private static array $columnCache = [];

    public function __construct(private readonly StoreMediaPayload $media) {}

    /**
     * @param  array{id:string,entity:string,entityId?:string,op:string,payload:array,createdAt?:int}  $envelope
     *
     * @throws SyncRejection
     */
    public function handle(array $envelope, ?string $originDevice = null): void
    {
        $entity = $envelope['entity'] ?? '';
        $op = $envelope['op'] ?? '';
        $payload = $envelope['payload'] ?? [];

        if (in_array($entity, (array) config('sync.device_local'), true)) {
            throw new SyncRejection(RejectReason::ForbiddenEntity);
        }

        /** @var class-string<SyncModel>|null $modelClass */
        $modelClass = config("sync.entities.$entity");
        if (! $modelClass) {
            throw new SyncRejection(RejectReason::UnknownEntity);
        }

        $id = $payload['id'] ?? null;
        if (! is_string($id) || $id === '') {
            throw new SyncRejection(RejectReason::InvalidPayload);
        }

        $existing = $modelClass::query()->find($id);

        if ($op === 'delete') {
            $this->applyDelete($modelClass, $existing, $id, $payload, $originDevice);

            return;
        }

        if ($op !== 'insert' && $op !== 'update') {
            throw new SyncRejection(RejectReason::InvalidPayload);
        }

        $this->applyUpsert($modelClass, $entity, $existing, $id, $payload, $originDevice);
    }

    private function applyDelete(string $modelClass, ?SyncModel $existing, string $id, array $payload, ?string $originDevice): void
    {
        $deletedAt = (int) ($payload['deleted_at'] ?? $payload['updated_at'] ?? 0);
        if ($deletedAt <= 0) {
            throw new SyncRejection(RejectReason::InvalidPayload);
        }

        if ($existing === null) {
            // Tombstone untuk row yang belum pernah kita lihat (urutan sync bebas).
            $model = new $modelClass;
            $model->id = $id;
            $model->origin_device = $originDevice;
            $model->created_at = $deletedAt;
            $model->updated_at = $deletedAt;
            $model->deleted_at = $deletedAt;
            $model->sync_version = 0;
            $model->save();

            return;
        }

        if ($deletedAt <= (int) $existing->updated_at) {
            throw new SyncRejection(RejectReason::Stale);
        }

        $existing->deleted_at = $deletedAt;
        $existing->updated_at = $deletedAt;
        $existing->sync_version = (int) $existing->sync_version + 1;
        $existing->save();
    }

    private function applyUpsert(string $modelClass, string $entity, ?SyncModel $existing, string $id, array $payload, ?string $originDevice): void
    {
        $updatedAt = (int) ($payload['updated_at'] ?? 0);
        if ($updatedAt <= 0) {
            throw new SyncRejection(RejectReason::InvalidPayload);
        }

        if ($existing !== null && $updatedAt <= (int) $existing->updated_at) {
            throw new SyncRejection(RejectReason::Stale);
        }

        $attrs = $this->pickColumns($modelClass, $payload);

        if ($entity === 'media') {
            $attrs = $this->handleMedia($id, $payload, $attrs);
        }

        // store_id, sync_version, origin_device dikelola server — jangan dari payload.
        unset($attrs['store_id'], $attrs['sync_version']);

        $model = $existing ?? new $modelClass;
        $model->forceFill($attrs);
        $model->id = $id;
        $model->origin_device = $originDevice ?? $model->origin_device;
        $model->sync_version = $existing ? (int) $existing->sync_version + 1 : 0;
        $model->save();
    }

    /** Ambil hanya key payload yang benar-benar kolom tabel (buang dirty/remote_id/dll). */
    private function pickColumns(string $modelClass, array $payload): array
    {
        $table = (new $modelClass)->getTable();
        $columns = self::$columnCache[$table] ??= Schema::getColumnListing($table);

        return array_intersect_key($payload, array_flip($columns));
    }

    private function handleMedia(string $id, array $payload, array $attrs): array
    {
        $data = $payload['data'] ?? null;
        if (is_string($data) && $data !== '') {
            if (strlen($data) > (int) config('sync.media_max_bytes')) {
                throw new SyncRejection(RejectReason::InvalidPayload);
            }
            $attrs['remote_url'] = $this->media->handle($id, $data, (string) ($payload['mime'] ?? 'image/jpeg'));
            $attrs['data'] = null; // byte pindah ke storage; DB tak menyimpan base64
        }

        return $attrs;
    }
}
