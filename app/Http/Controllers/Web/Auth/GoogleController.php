<?php

namespace App\Http\Controllers\Web\Auth;

use App\Actions\Auth\Web\LoginWithGoogleOauth;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

/**
 * Login Google untuk web (OAuth redirect). Jalur Android tetap memakai
 * verifikasi ID token di Api\V1\AuthController — keduanya berbagi
 * UpsertGoogleUser supaya user yang dihasilkan identik.
 */
class GoogleController extends Controller
{
    public function redirect(): SymfonyRedirect
    {
        abort_unless(filled(config('services.google.client_secret')), 404);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, LoginWithGoogleOauth $action): RedirectResponse
    {
        abort_unless(filled(config('services.google.client_secret')), 404);

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            // Pesan asli Socialite bocor detail konfigurasi — cukup dicatat.
            Log::warning('google oauth callback gagal', ['error' => $e->getMessage()]);

            return redirect()->route('login')
                ->with('error', 'Login Google gagal atau dibatalkan. Coba lagi.');
        }

        if (blank($googleUser->getEmail())) {
            return redirect()->route('login')
                ->with('error', 'Akun Google ini tidak memberikan alamat email.');
        }

        $action->handle($request, $googleUser);

        return redirect()->intended(route('dashboard'));
    }
}
