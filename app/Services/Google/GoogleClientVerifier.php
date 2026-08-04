<?php

namespace App\Services\Google;

use App\Contracts\GoogleTokenVerifier;
use Google\Client;

/**
 * Implementasi nyata: verifikasi ID token via SDK Google. Client id diambil dari
 * config services.google.client_id. Verifikasi memeriksa signature, issuer, aud,
 * dan expiry — jadi kita tidak perlu OAuth redirect, cukud token dari client.
 */
class GoogleClientVerifier implements GoogleTokenVerifier
{
    public function __construct(private readonly Client $client) {}

    public function verify(string $idToken): ?array
    {
        $payload = $this->client->verifyIdToken($idToken);
        if (! $payload || empty($payload['sub']) || empty($payload['email'])) {
            return null;
        }

        return [
            'sub' => $payload['sub'],
            'email' => $payload['email'],
            'name' => $payload['name'] ?? $payload['email'],
            'picture' => $payload['picture'] ?? null,
        ];
    }
}
