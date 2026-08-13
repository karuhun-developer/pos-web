<?php

namespace App\Actions\Auth\Web;

use App\Actions\Auth\EnsureUserHasStore;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Login web berbasis session (bukan Sanctum token seperti jalur Android).
 * Rate limit per email+IP supaya form login tidak jadi oracle password.
 */
class LoginWebSession
{
    public function __construct(private readonly EnsureUserHasStore $ensureStore) {}

    public function handle(Request $request, string $email, string $password, bool $remember = false): User
    {
        $key = 'login:'.mb_strtolower($email).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan. Coba lagi dalam '
                    .RateLimiter::availableIn($key).' detik.',
            ]);
        }

        if (! Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();
        $this->ensureStore->handle($user);

        return $user->refresh();
    }
}
