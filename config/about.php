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

];
