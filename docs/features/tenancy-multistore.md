# Fitur — Tenancy Multi-toko (isolasi data per store)

**Status:** ✅ Selesai (Phase 6)

## Tujuan
Menjamin data satu toko **tidak pernah bocor** ke toko lain, tanpa mempercayai
`store_id` dari payload client. Toko aktif ditentukan server dari token/header.

## Data & Aturan
- `store_id` **selalu diisi server-side**, tidak pernah dari payload client.
- Toko aktif diresolusi dari header `X-Store-Id`, fallback `user.current_store_id`.
  Bukan anggota toko → **403**.
- Read otomatis ter-scope: global scope `StoreScope` menambahkan
  `where store_id = StoreContext::id()` pada semua model sync.
- Write otomatis ter-scope: `SyncObserver::creating()` mengisi `store_id` dari
  `StoreContext` bila kosong.
- `StoreContext` dibersihkan tiap akhir request (`terminate()`) agar tidak bocor
  antar request/test.
- `stores` PK = **bigint** (bukan UUID) supaya kompatibel dengan `team_id`
  (unsignedBigInteger) spatie.
- Index komposit `(store_id, updated_at)` di tiap tabel entity → pull cepat & scope
  murah. `sales` unik `(store_id, number)` → nomor struk unik per toko.

## Kode
- `app/Support/StoreContext.php` — holder statis (set/get/id/has/clear).
- `app/Models/Scopes/StoreScope.php` — `#[ScopedBy]` di tiap model konkret.
- `app/Observers/SyncObserver.php` — `#[ObservedBy]` di tiap model konkret.
- `app/Http/Middleware/SetCurrentStore.php` — resolusi toko, set `StoreContext` +
  `setPermissionsTeamId`, `abort_if(403)`, clear di `terminate()`.
- `app/Models/SyncModel.php` (abstract base) + 8 model konkret.
- Test: isolasi tenant diverifikasi di `SyncPullTest.php` & `RbacTest.php`.
