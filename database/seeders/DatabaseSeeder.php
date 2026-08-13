<?php

namespace Database\Seeders;

use App\Actions\Auth\EnsureUserHasStore;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(SuperadminSeeder::class);

        $user = User::factory()->create([
            'name' => 'Pemilik Toko',
            'email' => 'owner@example.com',
        ]);

        app(EnsureUserHasStore::class)->handle($user);
    }
}
