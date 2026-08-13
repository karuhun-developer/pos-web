# Fitur — Autentikasi (Google ID token + OAuth redirect + Password)

**Status:** ✅ Selesai (Phase 6; jalur web ditambah di branch `feat/web-ui`)

## Tujuan
Login online untuk POS Kacaw tanpa alur OAuth redirect. Client mendapatkan **Google
ID token** dari Google Sign-In lalu mengirimnya ke server; server **memverifikasi**
token itu ke Google dan menerbitkan **Sanctum bearer token**. Disediakan juga login
email/password khusus dev & test. Setiap user baru otomatis punya satu toko + role
`owner`.

Sejak ada UI web, **browser memakai OAuth redirect** (Socialite) dan sesi cookie —
lihat bagian "Jalur web" di bawah. Dua jalur ini berbeda hanya pada cara mendapat klaim
dan cara menerbitkan sesi; pembuatan user & toko dibagi lewat Action yang sama
(`UpsertGoogleUser` + `EnsureUserHasStore`), jadi satu email selalu menghasilkan satu
user — login Google di web tidak pernah membuat akun kedua yang terpisah dari toko yang
sudah ada di Android.

## Data & Aturan
- Endpoint: `POST /auth/google {id_token}`, `POST /auth/login {email,password}`,
  `GET /auth/me`, `POST /auth/logout`.
- Verifikasi `id_token` via `Google\Client::verifyIdToken()` (client id dari
  `config/services.php` → `GOOGLE_CLIENT_ID`). Gagal → `401`.
- User dicocokkan `firstOrCreate` by `google_id`, fallback `email`. Kolom user
  tambahan: `google_id` (unik), `avatar_url`, `current_store_id`. `password`
  nullable (user Google tak punya password).
- User tanpa toko → dibuatkan store default, di-attach ke pivot `store_user` dengan
  `role='owner'`, di-`assignRole('owner')` (team = store), `current_store_id` diset.
- Response seragam: `{ token, user, stores[] }` (`stores[].role` dari pivot).
- `logout` mencabut personal access token yang dipakai request (→ `204`).
- Validasi 100% di FormRequest: `GoogleAuthRequest` (`id_token` required string),
  `LoginRequest` (`email`, `password` required). Tidak ada `validate()` di controller.

## Jalur web (session, bukan token)

| Route | Isi |
|---|---|
| `GET/POST masuk` (`login`) | form login email/password |
| `GET/POST daftar` (`register`) | registrasi + langsung login |
| `POST keluar` (`logout`) | logout + invalidate session + regenerate CSRF token |
| `GET auth/google/redirect` | `Socialite::driver('google')->redirect()` |
| `GET auth/google/callback` | tukar code → user → login session |

- Butuh **`GOOGLE_CLIENT_SECRET`** dan `GOOGLE_REDIRECT_URI` (`config/services.php`);
  tanpa client secret kedua route Google membalas **`404`** dan tombolnya tidak muncul
  di halaman login.
- `LoginWebSession` memberi **rate limit 5 percobaan per email+IP per 60 detik** supaya
  form login tidak jadi oracle password; percobaan ke-6 ditolak sebelum password-nya
  sempat dicek. Pesan gagalnya seragam ("Email atau password salah"), tidak
  membocorkan email mana yang terdaftar.
- Callback yang gagal/dibatalkan tidak menampilkan pesan asli Socialite (bocor detail
  konfigurasi) — dicatat di log, user diarahkan balik ke `login` dengan flash `error`.
  Akun Google tanpa email juga ditolak di sini.
- Session di-`regenerate()` setelah login (fixation) di ketiga Action web.

## Kode
- `app/Actions/Auth/UpsertGoogleUser.php` — upsert user dari klaim Google, dipakai
  **kedua** jalur (ID token Android & OAuth web).
- `app/Actions/Auth/Web/{LoginWebSession,RegisterWebUser,LoginWithGoogleOauth}.php`.
- `app/Http/Controllers/Web/Auth/{LoginController,RegisterController,GoogleController}.php`,
  `app/Http/Requests/Web/{LoginWebRequest,RegisterWebRequest}.php`.
- Test web: `tests/Feature/Web/WebAuthTest.php` (9 kasus; Socialite di-mock karena
  paketnya tidak punya `::fake()`).
- `app/Http/Controllers/Api/V1/GoogleAuthController.php`, `LoginController.php`,
  `AuthController.php` (me/logout) — semua route via atribut.
- `app/Actions/Auth/AuthenticateWithGoogle.php`, `LoginWithPassword.php`,
  `EnsureUserHasStore.php`.
- `app/Contracts/GoogleTokenVerifier.php` (interface) +
  `app/Services/Google/GoogleClientVerifier.php` (impl) — di-bind di
  `AppServiceProvider`; di test diganti `tests/Support/FakeGoogleVerifier` (token
  `"valid:<sub>:<email>"`).
- `app/Support/AuthResponse.php` — pembentuk payload `{token,user,stores}`.
- `app/Http/Requests/GoogleAuthRequest.php`, `LoginRequest.php`.
- Test: `tests/Feature/AuthGoogleTest.php`, `AuthLoginTest.php`.
