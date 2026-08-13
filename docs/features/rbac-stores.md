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
  `cashier.session`, `cashflow.manage`, `sale.void`.
- **Permission platform** (dipisah supaya tidak pernah ikut ter-assign ke owner sebuah
  toko): `platform.manage`, `donation.manage`.
- **Role**: `owner` = semua permission toko; `cashier` = `sync.push` + `sync.pull` +
  `cashier.session` + `cashflow.manage`; `superadmin` = permission platform.
- Toko aktif ditentukan header `X-Store-Id` (default `user.current_store_id`).
  `SetCurrentStore` **abort 403** bila user bukan anggota toko tsb, lalu set team id
  spatie agar cek permission ber-scope toko itu.
- Route sync diproteksi atribut `#[Middleware(['auth:sanctum','store','permission:sync.push'])]`
  / `permission:sync.pull`. Kurang izin → `403`.
- `GET /stores` mengembalikan daftar toko user + role per toko.

## Superadmin (lintas toko)

Role `superadmin` **tidak** ber-scope toko, dan itu membuatnya tidak bisa dipakai apa
adanya di atas paket spatie:

1. `HasRoles::roles()` selalu memfilter `wherePivot('team_id', getPermissionsTeamId())`.
   Begitu `SetCurrentStore` menyetel team id ke sebuah toko, `hasRole('superadmin')`
   untuk pivot bertim lain jadi `false`.
2. Kolom `model_has_roles.team_id` **NOT NULL** dan ikut jadi bagian primary key
   komposit, jadi assignment "tanpa tim" lewat `assignRole()` mustahil — percobaannya
   berakhir `SQLSTATE[23000]: NOT NULL constraint failed`.

Jalan keluarnya: **sentinel team id `0`**. Id toko selalu ≥ 1, jadi `0` aman dipakai
sebagai penanda "lintas platform".

- `app/Actions/Admin/SetSuperadmin.php` — satu-satunya tempat pivot itu ditulis
  (`grant()` / `revoke()`), langsung lewat query builder ke
  `Config::modelHasRolesTable()` dengan `team_id = SetSuperadmin::PLATFORM_TEAM`.
  Setelah menulis ia membersihkan cache: `Once::flush()` (memo `isSuperadmin()`) +
  `PermissionRegistrar::forgetCachedPermissions()`.
- `User::isSuperadmin()` — menanyakan pivot itu **langsung**, tanpa filter team, dimemo
  dengan `once()`. Relasi `roles()` milik spatie tidak bisa menjawabnya.
- `Gate::before()` di `AppServiceProvider` — superadmin lolos semua policy.
- Middleware `app/Http/Middleware/EnsureSuperadmin.php` (alias **`superadmin`**), bukan
  alias `role:` milik spatie, dipakai grup `routes/web/admin.php`.
- `php artisan pos:superadmin {email} [--revoke]` memakai Action yang sama, jadi CLI dan
  panel admin tidak pernah berbeda perilaku.

### Bikin akun superadmin pertama
Dua cara, keduanya lewat `SetSuperadmin` yang sama:

```bash
# a) user-nya sudah ada (mis. daftar lewat web/Google) → tinggal dinaikkan
php artisan pos:superadmin kamu@email.com

# b) server baru, belum ada siapa-siapa → isi SUPERADMIN_* di .env lalu
php artisan db:seed --class=SuperadminSeeder
```

`SuperadminSeeder` idempoten dan **melewati dirinya sendiri kalau
`SUPERADMIN_PASSWORD` kosong** — akun platform dengan password tebakan lebih berbahaya
daripada tidak ada akun sama sekali. Password akun yang sudah ada tidak pernah ditimpa
(menjalankan seeder lagi setelah deploy tidak boleh mengembalikan password lama), dan
akunnya ikut dibuatkan toko sendiri karena middleware `store` menolak semua halaman area
toko — termasuk `/dashboard`, tujuan setelah login — untuk user tanpa toko aktif.

## Kepemilikan data di route web

Policy di `app/Policies/` (trait `Concerns\ChecksStoreOwnership`) memeriksa berurutan:
row milik toko aktif → user anggota toko itu → aksi tulis butuh permission-nya.

Penting: pada route web, **policy-lah yang menghentikan akses lintas toko, bukan
`StoreScope`**. `SubstituteBindings` ada di grup `web` dan berjalan sebelum middleware
route `auth`/`store`, sehingga saat binding terjadi `StoreContext` masih kosong dan
scope-nya (fail-open) tidak memfilter. Akibatnya row milik toko lain tetap ter-resolve
dan ditolak dengan **`403`, bukan `404`**. Diuji di
`tests/Feature/Web/ProductOwnershipTest.php`.

## Kode
- `database/seeders/RolePermissionSeeder.php` — daftar PERMISSIONS & ROLES,
  didaftar global (`setPermissionsTeamId(null)`). Dipanggil `DatabaseSeeder`.
- `app/Http/Middleware/SetCurrentStore.php` — resolusi toko + set team id + 403.
- `app/Models/Store.php` (owner/users relations), pivot via `User::stores()`.
- `app/Http/Controllers/Api/V1/StoreController.php` (`GET /stores`).
- `bootstrap/app.php` — alias middleware `store`, `permission`, `role`,
  `role_or_permission`.
- `app/Actions/Admin/SetSuperadmin.php`, `ToggleSuperadmin.php`,
  `app/Console/Commands/MakeSuperadmin.php`, `app/Http/Middleware/EnsureSuperadmin.php`.
- `app/Policies/*` + `app/Policies/Concerns/ChecksStoreOwnership.php`.
- Test: `tests/Feature/RbacTest.php` (cashier ditolak `catalog.manage`; non-anggota
  → 403; isolasi antar toko), `tests/Feature/Web/ProductOwnershipTest.php`
  (kepemilikan lewat route web), `tests/Feature/Web/AdminPanelTest.php`
  (akses `/admin` + promosi/penurunan superadmin).
