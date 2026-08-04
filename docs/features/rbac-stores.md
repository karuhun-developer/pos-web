# Fitur — RBAC & Toko (multi-store, role per toko)

**Status:** ✅ Selesai (Phase 6)

## Tujuan
Satu akun bisa punya banyak toko, dengan **role berbeda per toko** (mis. `owner` di
toko sendiri, `cashier` di toko orang lain). Izin dibedakan: kasir boleh sync &
buka/tutup kasir tapi tidak boleh kelola katalog.

## Data & Aturan
- spatie/laravel-permission dengan **teams = store** (`config/permission.php`
  `teams => true`). `team_id` = `stores.id` (bigint).
- Pivot `store_user` menyimpan `role` (owner|cashier) + unik `(store_id, user_id)`.
- **Permission**: `sync.push`, `sync.pull`, `catalog.manage`, `reports.view`,
  `cashier.session`.
- **Role**: `owner` = semua permission; `cashier` = `sync.push` + `sync.pull` +
  `cashier.session`.
- Toko aktif ditentukan header `X-Store-Id` (default `user.current_store_id`).
  `SetCurrentStore` **abort 403** bila user bukan anggota toko tsb, lalu set team id
  spatie agar cek permission ber-scope toko itu.
- Route sync diproteksi atribut `#[Middleware(['auth:sanctum','store','permission:sync.push'])]`
  / `permission:sync.pull`. Kurang izin → `403`.
- `GET /stores` mengembalikan daftar toko user + role per toko.

## Kode
- `database/seeders/RolePermissionSeeder.php` — daftar PERMISSIONS & ROLES,
  didaftar global (`setPermissionsTeamId(null)`). Dipanggil `DatabaseSeeder`.
- `app/Http/Middleware/SetCurrentStore.php` — resolusi toko + set team id + 403.
- `app/Models/Store.php` (owner/users relations), pivot via `User::stores()`.
- `app/Http/Controllers/Api/V1/StoreController.php` (`GET /stores`).
- `bootstrap/app.php` — alias middleware `store`, `permission`, `role`,
  `role_or_permission`.
- Test: `tests/Feature/RbacTest.php` (cashier ditolak `catalog.manage`; non-anggota
  → 403; isolasi antar toko).
