<?php

namespace Tests\Support;

use App\Contracts\GoogleTokenVerifier;

/**
 * Verifier palsu untuk test: token "valid:<sub>:<email>" → klaim; selain itu null.
 * Menghindari panggilan nyata ke Google.
 */
class FakeGoogleVerifier implements GoogleTokenVerifier
{
    public function verify(string $idToken): ?array
    {
        if (! str_starts_with($idToken, 'valid:')) {
            return null;
        }

        [, $sub, $email] = array_pad(explode(':', $idToken), 3, null);

        return [
            'sub' => $sub ?? 'google-sub',
            'email' => $email ?? 'user@example.com',
            'name' => 'Google User',
            'picture' => 'https://example.com/avatar.png',
        ];
    }
}
