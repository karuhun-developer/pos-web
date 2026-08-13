<?php

namespace App\Actions\Auth;

use App\Contracts\GoogleTokenVerifier;
use App\Support\AuthResponse;
use Illuminate\Validation\ValidationException;

/**
 * Login via Google ID token: verifikasi token → firstOrCreate user (by google_id,
 * fallback email) → jamin punya toko → terbitkan Sanctum token. Melempar
 * ValidationException (422) bila token tak valid.
 */
class AuthenticateWithGoogle
{
    public function __construct(
        private readonly GoogleTokenVerifier $verifier,
        private readonly UpsertGoogleUser $upsertUser,
        private readonly EnsureUserHasStore $ensureStore,
    ) {}

    public function handle(string $idToken): array
    {
        $claims = $this->verifier->verify($idToken);
        if ($claims === null) {
            throw ValidationException::withMessages([
                'id_token' => 'Google ID token tidak valid.',
            ]);
        }

        $user = $this->upsertUser->handle($claims);

        $this->ensureStore->handle($user);
        $user->refresh();

        $token = $user->createToken('pos-kacaw')->plainTextToken;

        return AuthResponse::payload($user, $token);
    }
}
