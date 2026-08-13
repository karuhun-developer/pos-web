<?php

namespace App\Providers;

use App\Contracts\GoogleTokenVerifier;
use App\Models\User;
use App\Services\Google\GoogleClientVerifier;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GoogleClient::class, function () {
            $client = new GoogleClient;
            $client->setClientId((string) config('services.google.client_id'));

            return $client;
        });

        $this->app->bind(GoogleTokenVerifier::class, GoogleClientVerifier::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Superadmin lolos semua policy & permission. Mengembalikan null (bukan
        // false) untuk user biasa supaya pemeriksaan lain tetap berjalan —
        // termasuk Gate::before milik spatie yang mengecek permission.
        Gate::before(fn (User $user) => $user->isSuperadmin() ? true : null);
    }
}
