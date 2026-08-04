<?php

namespace App\Actions\Sync;

use App\Sync\SyncRejection;
use Illuminate\Support\Facades\DB;

/**
 * Meng-apply batch ChangeEnvelope → PushResult. Tiap envelope diproses dalam
 * transaksi terpisah: satu gagal tidak membatalkan yang lain. Key acked/rejected
 * = envelope.id (outbox row id). Lihat kontrak: docs/api-contract.md §3.
 */
class PushChanges
{
    public function __construct(private readonly ApplyChange $apply) {}

    /**
     * @param  array<int,array>  $changes
     * @return array{acked:list<string>,rejected:list<array{id:string,reason:string}>}
     */
    public function handle(array $changes, ?string $originDevice = null): array
    {
        $acked = [];
        $rejected = [];

        foreach ($changes as $envelope) {
            $id = $envelope['id'] ?? null;
            try {
                DB::transaction(fn () => $this->apply->handle($envelope, $originDevice));
                $acked[] = $id;
            } catch (SyncRejection $e) {
                $rejected[] = ['id' => $id, 'reason' => $e->reason->value];
            }
        }

        return ['acked' => $acked, 'rejected' => $rejected];
    }
}
