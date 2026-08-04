<?php

namespace App\Support;

use App\Models\User;

/**
 * Membentuk respons auth standar { token, user, stores } sesuai kontrak
 * docs/api-contract.md §2. Dipakai login Google & password + /auth/me.
 */
class AuthResponse
{
    public static function payload(User $user, ?string $token = null): array
    {
        $user->loadMissing('stores');

        $data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'current_store_id' => $user->current_store_id,
            ],
            'stores' => $user->stores->map(fn ($store) => [
                'id' => $store->id,
                'name' => $store->name,
                'role' => $store->pivot->role,
            ])->values(),
        ];

        if ($token !== null) {
            $data = ['token' => $token] + $data;
        }

        return $data;
    }
}
