<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Donasi
    |--------------------------------------------------------------------------
    |
    | POS Pro gratis dipakai; donasi murni sukarela dan tidak membuka fitur apa
    | pun. Yang ada di sini cuma aturan nominal — QRIS, nomor rekening, dan
    | tautan Saweria diatur superadmin lewat /admin/donasi/pengaturan, karena
    | ketiganya berubah tanpa alasan teknis dan tidak layak menunggu deploy.
    |
    */

    /** Nominal pilihan cepat, rupiah bulat. */
    'presets' => [10_000, 25_000, 50_000, 100_000, 250_000],

    'min' => (int) env('DONATION_MIN', 5_000),
    'max' => (int) env('DONATION_MAX', 50_000_000),

    /** Jumlah donatur yang tampil di dinding, setelah lolos moderasi. */
    'wall_limit' => 12,

];
