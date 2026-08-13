<?php

namespace App\Actions\Auth;

use App\Models\User;

/**
 * Menemukan/membuat user dari klaim Google. Dipakai dua jalur login yang
 * berbeda tapi harus menghasilkan user yang sama persis: verifikasi ID token
 * (Android, AuthenticateWithGoogle) dan OAuth redirect (web, Socialite).
 *
 * Pencocokan: google_id dulu, lalu email — supaya akun yang lahir dari
 * register email/password tidak terduplikasi saat pemiliknya login Google.
 */
class UpsertGoogleUser
{
    /**
     * @param  array{sub:string,email:string,name?:string|null,picture?:string|null}  $claims
     */
    public function handle(array $claims): User
    {
        $user = User::query()->where('google_id', $claims['sub'])->first()
            ?? User::query()->where('email', $claims['email'])->first()
            ?? new User;

        $user->fill([
            'name' => $user->name ?: ($claims['name'] ?? $claims['email']),
            'email' => $claims['email'],
            'google_id' => $claims['sub'],
            'avatar_url' => $claims['picture'] ?? $user->avatar_url,
        ]);
        $user->save();

        return $user;
    }
}
