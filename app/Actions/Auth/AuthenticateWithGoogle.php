<?php

namespace App\Actions\Auth;

use App\Contracts\GoogleTokenVerifier;
use App\Models\User;
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

        $this->ensureStore->handle($user);
        $user->refresh();

        $token = $user->createToken('pos-kacaw')->plainTextToken;

        return AuthResponse::payload($user, $token);
    }
}
