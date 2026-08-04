<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Support\AuthResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Login email/password (untuk dev & test). Bentuk respons sama dengan login
 * Google. Boleh dimatikan di produksi.
 */
class LoginWithPassword
{
    public function __construct(private readonly EnsureUserHasStore $ensureStore) {}

    public function handle(string $email, string $password): array
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user || ! $user->password || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        $this->ensureStore->handle($user);
        $user->refresh();

        $token = $user->createToken('pos-kacaw')->plainTextToken;

        return AuthResponse::payload($user, $token);
    }
}
