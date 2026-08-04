<?php

namespace App\Support;

use App\Models\Store;

/**
 * Menyimpan toko aktif untuk request/proses saat ini. Dibaca oleh StoreScope
 * (global scope tenant) & SyncObserver, di-set oleh SetCurrentStore middleware
 * atau manual di test. Ini satu-satunya sumber "store_id" — payload client
 * TIDAK pernah dipercaya untuk menentukan tenant.
 */
class StoreContext
{
    private static ?Store $store = null;

    public static function set(?Store $store): void
    {
        self::$store = $store;
    }

    public static function get(): ?Store
    {
        return self::$store;
    }

    public static function id(): ?int
    {
        return self::$store?->id;
    }

    public static function has(): bool
    {
        return self::$store !== null;
    }

    public static function clear(): void
    {
        self::$store = null;
    }
}
