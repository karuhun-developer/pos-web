<?php

namespace App\Exceptions;

use Exception;

/**
 * Gagal berkomunikasi dengan Paywuz. Ditangkap di controller donasi dan
 * ditampilkan sebagai pesan biasa — donaturnya tidak perlu melihat 500.
 */
class PaywuzException extends Exception {}
