<?php

namespace App\Providers;

use App\Contracts\GoogleTokenVerifier;
use App\Services\Google\GoogleClientVerifier;
use Google\Client as GoogleClient;
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
        //
    }
}
