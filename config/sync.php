<?php

use App\Models\CashflowCategory;
use App\Models\CashflowEntry;
use App\Models\CashierSession;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;

return [

    /*
    |--------------------------------------------------------------------------
    | Entity allowlist
    |--------------------------------------------------------------------------
    | Nama entity (sesuai nama tabel di FE POS Kacaw) → model server yang
    | menangani upsert-nya. Hanya entity di sini yang boleh disync. Lihat
    | kontrak: docs/api-contract.md §3.3.
    */
    'entities' => [
        'categories' => Category::class,
        'products' => Product::class,
        'media' => Media::class,
        'cashier_sessions' => CashierSession::class,
        'sales' => Sale::class,
        'sale_items' => SaleItem::class,
        'cashflow_categories' => CashflowCategory::class,
        'cashflow_entries' => CashflowEntry::class,
    ],

    /*
    | Entity yang dikenal FE tapi SENGAJA tidak disync (device-local).
    | Push untuk ini → rejected: forbidden_entity.
    */
    'device_local' => [
        'settings',
    ],

    /*
    | Batas ukuran payload media (byte base64) sebelum ditolak (413).
    */
    'media_max_bytes' => 8 * 1024 * 1024,

    /*
    | Disk penyimpanan byte media (seam ke object storage saat produksi).
    */
    'media_disk' => env('SYNC_MEDIA_DISK', 'public'),
];
