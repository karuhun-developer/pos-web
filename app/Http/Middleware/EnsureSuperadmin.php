<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi route ke superadmin platform. Memakai User::isSuperadmin() yang
 * lepas dari konteks toko — alias 'role:superadmin' milik spatie tidak bisa
 * dipakai di sini (lihat komentar di User::isSuperadmin()).
 */
class EnsureSuperadmin
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_if($user === null || ! $user->isSuperadmin(), 403, 'Khusus superadmin.');

        return $next($request);
    }
}
