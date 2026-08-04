# Fitur — Endpoint Sinkronisasi (push / pull)

**Status:** ✅ Selesai (Phase 6)

## Tujuan
Menerima perubahan dari device (`push`) dan menyebarkannya ke device lain (`pull`)
untuk 8 entity, aman-konflik (LWW) dan tanpa kehilangan data. Bentuk JSON **byte-
compatible** dengan tipe FE (`PushResult`/`PullResult`).

## Data & Aturan
- **Allowlist 8 entity** (`config/sync.php`): `categories, products, media,
  cashier_sessions, sales, sale_items, cashflow_categories, cashflow_entries`.
  `settings` = device-local → reject `forbidden_entity`.
- **push** `POST /sync/push {changes: ChangeEnvelope[]}` →
  `{acked: string[], rejected: {id,reason}[]}` di-key **outbox id** (`envelope.id`).
  - Per envelope, transaksi terpisah. Alur `ApplyChange`:
    device-local? → `forbidden_entity`; di allowlist? → `unknown_entity`; ada `id`? →
    `invalid_payload`; `op=delete` → tombstone (`deleted_at` di-set; LWW); else upsert.
  - **LWW**: apply hanya jika `payload.updated_at > stored.updated_at`, else `stale`
    (bukan error, sekadar dilewati). `sync_version` di-increment (audit).
  - `store_id` diisi server-side; kolom di-whitelist via `Schema::getColumnListing`
    (kolom FE-lokal `dirty`/`remote_id` dibuang).
- **pull** `GET /sync/pull?entity=&since=` → `{entity, changes[], cursor}`.
  - `where updated_at > since` termasuk tombstone, urut `updated_at ASC`.
  - `cursor` = max `updated_at` (atau `since` bila kosong).
  - Payload buang `store_id`/`origin_device`, tambah `dirty=0`/`remote_id=null`;
    media `data=null` (lihat media-storage.md).
- Reason enum: `unknown_entity | forbidden_entity | invalid_payload | stale | forbidden`.
- Validasi via FormRequest: `SyncPushRequest` (`changes.*.op` in insert/update/delete,
  `payload` array), `SyncPullRequest` (`entity` Rule::in allowlist, `since` integer).

## Kode
- `app/Actions/Sync/PushChanges.php`, `ApplyChange.php`, `PullChanges.php`.
- `app/Sync/RejectReason.php` (enum), `SyncRejection.php` (exception, `->reason`).
- `app/Http/Controllers/Api/V1/SyncPushController.php`, `SyncPullController.php`
  (route + middleware `permission:sync.push` / `sync.pull` via atribut).
- `app/Http/Requests/SyncPushRequest.php`, `SyncPullRequest.php`.
- `config/sync.php` — allowlist entity + device_local.
- Test: `tests/Feature/SyncPushTest.php`, `SyncPullTest.php`,
  `tests/Unit/ApplyChangeTest.php`.
