<?php

namespace App\Actions\Admin;

use App\Models\User;

/**
 * Menaikkan/menurunkan status superadmin dari panel platform.
 *
 * @return bool true kalau user JADI superadmin, false kalau statusnya dicabut
 */
class ToggleSuperadmin
{
    public function __construct(private readonly SetSuperadmin $superadmin) {}

    public function handle(User $user): bool
    {
        $promote = ! $user->isSuperadmin();

        $promote
            ? $this->superadmin->grant($user)
            : $this->superadmin->revoke($user);

        return $promote;
    }
}
