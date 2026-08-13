<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Donasi
    |--------------------------------------------------------------------------
    |
    | POS Pro gratis dipakai; donasi murni sukarela. Tiga kanal disediakan dan
    | masing-masing hidup sendiri-sendiri: kanal yang datanya belum diisi di
    | .env otomatis tidak muncul di halaman /dukung — tidak ada tombol yang
    | mengarah ke rekening kosong.
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

    /** Menit sebelum tagihan Paywuz kedaluwarsa. */
    'checkout_expiry_minutes' => (int) env('DONATION_EXPIRY_MINUTES', 60),

    /** Tautan ke platform donasi pihak ketiga. */
    'external' => [
        ['label' => 'Saweria', 'url' => env('DONATION_SAWERIA_URL')],
        ['label' => 'Trakteer', 'url' => env('DONATION_TRAKTEER_URL')],
    ],

    /** Jumlah nama yang tampil di dinding donatur. */
    'wall_limit' => 20,

];
