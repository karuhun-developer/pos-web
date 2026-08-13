<?php

namespace App\Actions\Auth\Web;

use App\Actions\Auth\EnsureUserHasStore;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Registrasi lewat web: bikin user, jamin punya toko (EnsureUserHasStore bikin
 * toko pertama + role owner), lalu langsung login session.
 */
class RegisterWebUser
{
    public function __construct(private readonly EnsureUserHasStore $ensureStore) {}

    public function handle(Request $request, string $name, string $email, string $password): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password, // di-hash otomatis via cast 'hashed'
        ]);

        $this->ensureStore->handle($user);

        Auth::login($user);
        $request->session()->regenerate();

        return $user->refresh();
    }
}
