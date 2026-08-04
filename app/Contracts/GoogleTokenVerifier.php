<?php

namespace App\Contracts;

/**
 * Memverifikasi Google ID token (dikirim client) dan mengembalikan klaim profil.
 * Di-abstract-kan supaya test bisa mem-bind fake tanpa memanggil Google.
 */
interface GoogleTokenVerifier
{
    /**
     * @return array{sub:string,email:string,name?:string,picture?:string}|null
     *                                                                          null bila token invalid.
     */
    public function verify(string $idToken): ?array;
}
