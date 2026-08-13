<?php

namespace App\Actions\Auth\Web;

use App\Actions\Auth\EnsureUserHasStore;
use App\Actions\Auth\UpsertGoogleUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Login web via OAuth redirect Google (Socialite). Bedanya dengan
 * AuthenticateWithGoogle (Android) hanya di cara mendapatkan klaim dan cara
 * menerbitkan sesi — pembuatan user & toko-nya berbagi Action yang sama.
 */
class LoginWithGoogleOauth
{
    public function __construct(
        private readonly UpsertGoogleUser $upsertUser,
        private readonly EnsureUserHasStore $ensureStore,
    ) {}

    public function handle(Request $request, SocialiteUser $googleUser): User
    {
        $user = $this->upsertUser->handle([
            'sub' => (string) $googleUser->getId(),
            'email' => (string) $googleUser->getEmail(),
            'name' => $googleUser->getName(),
            'picture' => $googleUser->getAvatar(),
        ]);

        $this->ensureStore->handle($user);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return $user->refresh();
    }
}
