<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Halaman
    |--------------------------------------------------------------------------
    |
    | Bawaan paket menunjuk ke resource_path('js/pages') huruf kecil, sedangkan
    | proyek ini memakai `resources/js/Pages` (huruf besar, seperti POS Kacaw).
    | Di Linux nama direktori bersifat case-sensitive, jadi tanpa berkas ini
    | assertInertia()->component() selalu gagal dengan "page component file
    | does not exist" meski berkasnya jelas ada.
    |
    */

    'pages' => [

        /*
         * Menyala di luar produksi: halaman yang berkas komponennya tidak ada
         * hanya ketahuan di browser ("Page not found: ./Pages/Dashboard.vue"),
         * bukan oleh test — dengan ini render-nya gagal keras di CI, dan
         * PageSmokeTest yang menyusuri semua route GET jadi punya gigi.
         */
        'ensure_pages_exist' => env('APP_ENV') !== 'production',

        'paths' => [
            resource_path('js/Pages'),
        ],

        'extensions' => [
            'vue',
        ],

    ],

];
