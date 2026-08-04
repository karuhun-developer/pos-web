<?php

namespace App\Sync;

use RuntimeException;

/**
 * Dilempar saat satu envelope tidak bisa di-apply. Ditangkap per-envelope oleh
 * PushChanges → jadi entri PushResult.rejected. Tidak membatalkan envelope lain.
 */
class SyncRejection extends RuntimeException
{
    public function __construct(public readonly RejectReason $reason)
    {
        parent::__construct($reason->value);
    }
}
