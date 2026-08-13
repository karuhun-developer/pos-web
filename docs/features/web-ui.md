# Fitur — UI Web (Inertia + Vue 3)

**Status:** ✅ Selesai (branch `feat/web-ui`)

## Tujuan
POS Pro tidak lagi murni API. Ada dua area web:

1. **Area toko** — pemilik/kasir login lewat browser, mengelola katalog, transaksi,
   arus kas, laporan, dan impor/ekspor. Semua perubahannya **ikut ter-pull perangkat
   Android** karena ditulis lewat jalur sync yang sama.
2. **Area platform (`/admin`)** — superadmin melihat seluruh toko, pengguna, dan
   donasi lintas tenant.

## Stack
- **Inertia 2** + **Vue 3** + **Tailwind v4**, Vite, TypeScript (`vue-tsc --noEmit`).
- **Ziggy** (`@routes` di `resources/views/app.blade.php`) → `route()` tersedia di Vue.
- **ECharts** lewat `vue-echarts` (lihat bagian Laporan).
- Ikon: `@lucide/vue`. Copy UI **Bahasa Indonesia**, seperti POS Kacaw.

## Route: kenapa tidak pakai atribut
Route halaman ada di `routes/web/{guest,app,admin}.php` yang di-`require` dari
`routes/web.php`, **bukan** lewat `spatie/laravel-route-attributes`. Config default
paket itu hanya memasang `SubstituteBindings`, jadi controller beratribut tidak dapat
session, CSRF, maupun middleware Inertia. Controller **API** tetap memakai atribut.

| Berkas | Isi | Middleware |
|---|---|---|
| `routes/web/guest.php` | landing, login/daftar, OAuth Google, halaman donasi publik | `guest` / publik |
| `routes/web/app.php` | dashboard, produk, kategori, transaksi, kas, sesi, laporan, impor/ekspor, pengaturan toko | `auth`, **`store`** |
| `routes/web/admin.php` | area platform | `auth`, **`superadmin`** |

## Aturan yang tidak boleh dilanggar

### 1. Menulis HANYA lewat `WriteEntity`
`SyncModel` punya `$timestamps = false` dan `updated_at` epoch **ms**. Menulis dengan
Eloquent biasa membuat `updated_at` tidak bergerak → `PullChanges` (`where updated_at >
since`) **tidak akan pernah** membawa row itu ke Android: datanya "ada" tapi tidak
pernah sampai ke kasir.

`app/Actions/Sync/WriteEntity.php` adalah satu-satunya pintu tulis dari server. Ia
membungkus `ApplyChange` yang sama dengan `POST /sync/push`, jadi ikut mendapat LWW,
tombstone (hapus = `deleted_at`, bukan hard delete), `pickColumns()`, `sync_version`,
dan konversi media base64 → storage. `originDevice` diisi `"web:{user_id}"`.

Semua Action katalog/kas (`app/Actions/{Catalog,Cashflow}/…`) dan importer memakainya.
Uji regresinya ada di `tests/Feature/Web/WebSyncTest.php` — kalau test itu merah,
perubahan web berhenti di server.

### 2. Konteks toko wajib untuk setiap halaman toko
`StoreScope` **fail-open**: tanpa toko aktif, query tidak dibatasi sama sekali. Karena
itu seluruh grup `routes/web/app.php` memakai middleware `store` (`SetCurrentStore`).
Area `/admin` sengaja tidak memakainya dan menulis
`withoutGlobalScope(StoreScope::class)` **eksplisit** di tiap query lintas tenant.

### 3. Kepemilikan data dicek policy, bukan scope
`SubstituteBindings` berada di grup `web` dan berjalan **sebelum** middleware route
`auth`/`store`. Saat route-model binding terjadi, `StoreContext` masih kosong sehingga
`StoreScope` tidak memfilter apa pun dan row milik toko lain **tetap ketemu**. Yang
menghentikannya adalah **policy → 403** (bukan 404). Ini disengaja dan diuji di
`tests/Feature/Web/ProductOwnershipTest.php`.

Policy di `app/Policies/` memakai trait `Concerns\ChecksStoreOwnership`:
row harus milik toko aktif → user harus anggota toko itu → aksi tulis butuh permission
(`catalog.manage`, dst.). Superadmin lolos lebih dulu lewat `Gate::before`.

### 4. Props share wajib closure, bukan nilai jadi
`Inertia\Middleware::handle()` memanggil `share()` **sebelum** `$next($request)`. Blok
share yang dievaluasi langsung berjalan **sebelum** middleware `store`, jadi
`StoreContext` masih kosong: `auth.current_store` null dan `auth.user.permissions`
kosong. Gejalanya halus dan cuma kelihatan di browser — semua tombol tulis lenyap
karena UI menyembunyikannya berdasarkan permission. Karena itu blok `auth` di
`HandleInertiaRequests` berupa **closure**, yang baru diresolve saat halaman dirender
(di dalam controller, setelah semua middleware jalan).
Dikunci `tests/Feature/Web/PageSmokeTest.php`.

## Halaman area toko

| Halaman | Route | Isi |
|---|---|---|
| Dashboard | `dashboard` | KPI hari ini, transaksi terakhir |
| Produk | `products.*` | daftar + cari + filter kategori, form (gambar, barcode, stok), hapus |
| Kategori | `categories.*` | nama, warna, urutan |
| Transaksi | `sales.*` | daftar, detail struk, **batalkan** (`sale.void`) |
| Arus kas | `cashflow.*` | entri masuk/keluar + kelola kategori kas |
| Sesi kasir | `sessions.index` | riwayat buka/tutup laci + selisih |
| Laporan | `reports.index` | lihat `docs/features/` bagian laporan di bawah |
| Impor/Ekspor | `io.*` | lihat [import-export.md](import-export.md) |
| Pengaturan toko | `store.edit` | ganti nama, daftar anggota |
| Ganti toko aktif | `stores.switch` | set `current_store_id` (validasi keanggotaan) |

## Laporan & chart
Action di `app/Actions/Report/*` mengembalikan array siap-render. **Bucketing tanggal
dilakukan di PHP** (Carbon, zona `Asia/Jakarta`) setelah mengambil row dalam rentang —
menghindari SQL khusus driver untuk epoch-ms dan bug timezone.

Satu `PeriodFilter` di atas halaman men-scope **semua** panel, jadi angka di seluruh
layar selalu berasal dari rentang yang sama.

| Panel | Bentuk |
|---|---|
| Omzet, transaksi, rata-rata keranjang, laba kotor | stat tile + delta vs periode sebelumnya |
| Tren penjualan vs periode sebelumnya | line 2 seri |
| Jam ramai | heatmap sequential |
| Produk terlaris | bar horizontal, top 10 + "Lainnya" |
| Komposisi metode bayar | stacked bar (bukan pie) |
| HPP vs margin per kategori | stacked bar |
| Arus kas per hari | diverging bar |
| Selisih laci per sesi | diverging |
| Stok menipis & nilai inventori | tabel + stat tile |

Aturan visualisasi yang dipegang: **tidak ada dual-axis**, urutan hue kategorikal tetap
dan tidak pernah didaur ulang, sequential = satu hue, legend hadir untuk ≥2 seri, dan
**setiap chart punya tampilan tabel** (`ChartCard` mewajibkan slot `#table`). Dark mode
punya langkah warna sendiri, bukan pembalikan otomatis.

Palet (divalidasi dengan `scripts/validate_palette.js`, bukan dikira-kira):

```
light: #2a78d6 #eb6834 #1baf7a #eda100 #e87ba4 #008300 #4a3aa7 #e34948
dark:  #3987e5 #d95926 #199e70 #c98500 #d55181 #008300 #9085e9 #e66767
```

Tema ECharts: `resources/js/charts/theme.ts`.

## Kode
- `app/Http/Middleware/HandleInertiaRequests.php` — share `auth.user`
  (+ `is_superadmin`), daftar toko & toko aktif (pakai ulang `AuthResponse::payload()`),
  dan flash message.
- `app/Http/Controllers/Web/**` — tipis; validasi 100% di
  `app/Http/Requests/Web/**`, logika di Action.
- `resources/js/Layouts/{AppLayout,AdminLayout,GuestLayout}.vue`,
  `resources/js/Components/ui/*` (Button, Input, Card, DataTable, Modal, Badge,
  Pagination, EmptyState), `resources/js/Components/charts/*`.
- `config/inertia.php` — `pages.paths` diarahkan ke `resources/js/Pages` (huruf besar);
  tanpa ini `assertInertia()->component()` selalu gagal di Linux yang case-sensitive.
  `pages.ensure_pages_exist` menyala di luar produksi supaya halaman yang komponennya
  belum dibuat gagal keras di test, bukan jadi "Page not found" di browser.
- Test: `tests/Feature/Web/{ProductOwnershipTest,WebSyncTest,AdminPanelTest,WebAuthTest,
  ImportExportTest,DonationTest,PageSmokeTest}.php`.

## Area platform (`/admin`)
Lihat juga [rbac-stores.md](rbac-stores.md) untuk mekanisme role superadmin.

| Halaman | Isi |
|---|---|
| Ringkasan | KPI platform, pertumbuhan toko & pengguna (line 2 seri), omzet & donasi per bulan, aktivitas terbaru |
| Toko | daftar lintas tenant + cari, drill-down: KPI, anggota, 10 transaksi terakhir |
| Pengguna | daftar + cari, tombol jadikan/cabut superadmin (menolak menurunkan diri sendiri) |
| Donasi | filter kanal/status/tanggal, total, chart per bulan, tandai lunas, ekspor CSV |
