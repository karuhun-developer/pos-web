<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas rilis
    |--------------------------------------------------------------------------
    |
    | Dipakai halaman /tentang. Versinya dibaca dari env supaya server yang
    | sedang berjalan bisa menyebut versinya sendiri tanpa ganti kode — pipeline
    | deploy tinggal mengisi APP_VERSION dari tag git. Tautan repo sengaja
    | konstan: mengubahnya memang perubahan kode, bukan konfigurasi server.
    |
    */

    'version' => env('APP_VERSION', '0.1.0-dev'),

    'repository' => 'https://github.com/karuhun-developer/pos-web',

    'android_repository' => 'https://github.com/karuhun-developer/pos-android',

    /*
     * Unduhan APK diarahkan ke halaman "latest release" GitHub, bukan ke berkas
     * versi tertentu: tautan ke APK spesifik jadi basi tiap rilis dan diam-diam
     * membagikan versi lama.
     */
    'android_download' => 'https://github.com/karuhun-developer/pos-android/releases/latest',

    /*
    |--------------------------------------------------------------------------
    | Akun superadmin awal
    |--------------------------------------------------------------------------
    |
    | Dipakai SuperadminSeeder. Dibaca lewat config (bukan env() langsung di
    | seeder) supaya tetap jalan di server yang config-nya di-cache — di sana
    | .env memang tidak ikut dimuat.
    |
    | Tanpa SUPERADMIN_PASSWORD, seeder-nya melewati diri sendiri: akun dengan
    | password tebakan jauh lebih berbahaya daripada tidak ada akun sama sekali.
    |
    */

    'superadmin' => [
        'name' => env('SUPERADMIN_NAME', 'Superadmin'),
        'email' => env('SUPERADMIN_EMAIL', 'superadmin@pospro.test'),
        'password' => env('SUPERADMIN_PASSWORD'),
    ],

];
