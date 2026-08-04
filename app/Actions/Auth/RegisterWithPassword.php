<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Support\AuthResponse;

/**
 * Registrasi akun baru via email/password. Membuat user, menjamin toko default
 * (owner) lewat EnsureUserHasStore, lalu menerbitkan Sanctum token. Bentuk
 * respons sama dengan login { token, user, stores } (docs/api-contract.md §2).
 */
class RegisterWithPassword
{
    public function __construct(private readonly EnsureUserHasStore $ensureStore) {}

    public function handle(string $name, string $email, string $password): array
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password, // di-hash otomatis via cast 'hashed'
        ]);

        $this->ensureStore->handle($user);
        $user->refresh();

        $token = $user->createToken('pos-kacaw')->plainTextToken;

        return AuthResponse::payload($user, $token);
    }
}
