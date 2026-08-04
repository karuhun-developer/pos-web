<?php

namespace App\Sync;

/**
 * Alasan sebuah ChangeEnvelope ditolak saat push. Nilai string ini masuk ke
 * PushResult.rejected[].reason dan dibaca FE (outbox.last_error).
 * Lihat kontrak: docs/api-contract.md §3.
 */
enum RejectReason: string
{
    case UnknownEntity = 'unknown_entity';
    case ForbiddenEntity = 'forbidden_entity';
    case InvalidPayload = 'invalid_payload';
    case Stale = 'stale';
    case Forbidden = 'forbidden';
}
