<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Judul & deskripsi halaman, dibangun di SERVER.
 *
 * Panel ini Inertia TANPA SSR: <Head> milik Inertia baru mengisi <title> dan
 * <meta name="description"> setelah bundle JS dijalankan. Perayap yang tidak
 * mengeksekusi JS — dan hampir semua scraper pratinjau tautan (WhatsApp,
 * Telegram, Twitter, Facebook) — cuma membaca HTML pertama dari
 * app.blade.php, yang isinya <div id="app"> kosong. Jadi meta-nya ditentukan
 * dari nama route di sini, dicetak Blade, lalu prop `seo` dibagikan ke Vue
 * supaya sisi klien memakai teks yang sama persis saat pindah halaman.
 */
final class PageSeo
{
    /**
     * Merek yang dipajang ke publik adalah aplikasi Androidnya, bukan nama
     * panel webnya. Template judulnya kembar dengan resources/js/app.ts —
     * yang satu dipakai sebelum JS jalan, yang satu sesudahnya; ubah dua-duanya
     * kalau formatnya diganti (dijaga tests/Feature/Web/PageSeoTest.php).
     */
    public const BRAND = 'POS Kacaw';

    private const DEFAULT_DESCRIPTION = 'POS Kacaw: aplikasi kasir Android gratis yang tetap jalan '
        .'offline, lalu sinkron sendiri ke panel web untuk kelola produk dan baca laporan.';

    /**
     * Hanya halaman publik yang didaftar. Route lain (dashboard, /admin, dan
     * apa pun yang butuh login) sengaja jatuh ke default `index => false`:
     * halaman di balik login tidak ada gunanya di hasil pencarian, dan
     * daftar-putih begini tidak bisa lupa menutup halaman baru.
     *
     * @var array<string, array{title: string, description: string, index?: bool}>
     */
    private const PAGES = [
        'home' => [
            'title' => 'Aplikasi kasir Android yang jalan tanpa internet',
            'description' => 'POS Kacaw: aplikasi kasir (POS) Android gratis untuk warung & UMKM. '
                .'Tetap jalan offline, lalu sinkron sendiri ke panel web untuk kelola produk, '
                .'atur hak akses kasir, dan baca laporan. Gratis 100%, open source.',
            'index' => true,
        ],
        'donate.index' => [
            // Bukan 'Dukung POS Kacaw': template judulnya sudah menempelkan
            // nama mereknya, jadi hasilnya "Dukung POS Kacaw · POS Kacaw".
            'title' => 'Dukung',
            'description' => 'Ikut menopang biaya server POS Kacaw lewat QRIS, transfer bank, '
                .'atau Saweria. Aplikasinya tetap 100% gratis — donasinya murni sukarela.',
            'index' => true,
        ],
        'donate.thanks' => [
            'title' => 'Terima kasih',
            'description' => 'Catatan donasimu sudah masuk dan sedang ditinjau.',
        ],
        'register' => [
            'title' => 'Daftar',
            'description' => 'Bikin akun panel web POS Kacaw gratis. Toko pertama dibuatkan '
                .'otomatis dan kamu jadi pemiliknya.',
            'index' => true,
        ],
        'login' => [
            'title' => 'Masuk',
            'description' => 'Masuk ke panel web untuk kelola produk, transaksi, dan laporan tokomu.',
        ],
    ];

    /**
     * @return array{title: string|null, title_full: string, description: string, index: bool, url: string}
     */
    public static function for(Request $request): array
    {
        $page = self::PAGES[$request->route()?->getName()] ?? [];

        return [
            'title' => $page['title'] ?? null,
            'title_full' => self::title($page['title'] ?? null),
            'description' => $page['description'] ?? self::DEFAULT_DESCRIPTION,
            'index' => $page['index'] ?? false,
            // Tanpa query string: ?page=2&sort=nama cuma bikin URL kembar di indeks.
            'url' => $request->url(),
        ];
    }

    /** Judul lengkap satu halaman; null = halaman tanpa judul sendiri. */
    public static function title(?string $title): string
    {
        return $title === null || $title === '' ? self::BRAND : $title.' · '.self::BRAND;
    }
}
