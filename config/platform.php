<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas rilis
    |--------------------------------------------------------------------------
    |
    | Tautan repo sengaja konstan: mengubahnya memang perubahan kode, bukan
    | konfigurasi server. Nomor versi aplikasi Android TIDAK ditulis di sini —
    | App\Actions\Platform\FetchAndroidRelease membacanya langsung dari rilis
    | GitHub, supaya angka yang dipajang tidak pernah ketinggalan dari rilis
    | sebenarnya.
    |
    */

    'repository' => 'https://github.com/karuhun-developer/pos-web',

    'android_repository' => 'https://github.com/karuhun-developer/pos-android',

    /*
     * Cadangan kalau GitHub tidak bisa dihubungi: halaman "latest release",
     * bukan berkas APK versi tertentu — tautan ke satu berkas jadi basi tiap
     * rilis dan diam-diam membagikan versi lama.
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
