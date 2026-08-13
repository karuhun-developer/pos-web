<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Donasi
    |--------------------------------------------------------------------------
    |
    | POS Pro gratis dipakai; donasi murni sukarela. Yang ada di sini cuma aturan
    | nominal; nomor rekening, QRIS, dan tautan Saweria diatur superadmin lewat
    | halaman pengaturan donasi karena bisa berubah tanpa deploy.
    |
    */

    /** Nominal pilihan cepat, rupiah bulat. */
    'presets' => [10_000, 25_000, 50_000, 100_000, 250_000],

    'min' => (int) env('DONATION_MIN', 5_000),
    'max' => (int) env('DONATION_MAX', 50_000_000),

    /*
    | Transfer manual: DICATAT SAJA, tanpa verifikasi. Ini keputusan sadar —
    | catatan donasi tidak membuka fitur apa pun, jadi tidak ada yang bisa
    | dicurangi dengan mengaku sudah transfer.
    */
    'manual' => [
        'bank' => env('DONATION_BANK_NAME'),
        'account_number' => env('DONATION_BANK_ACCOUNT'),
        'account_name' => env('DONATION_BANK_HOLDER'),
        'qris_url' => env('DONATION_QRIS_URL'),
    ],

    /** Tautan ke platform donasi pihak ketiga. */
    'external' => [
        ['label' => 'Saweria', 'url' => env('DONATION_SAWERIA_URL')],
        ['label' => 'Trakteer', 'url' => env('DONATION_TRAKTEER_URL')],
    ],

    /** Jumlah nama yang tampil di dinding donatur. */
    'wall_limit' => 20,

];
