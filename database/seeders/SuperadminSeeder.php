<?php

namespace Database\Seeders;

use App\Actions\Admin\SetSuperadmin;
use App\Actions\Auth\EnsureUserHasStore;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun superadmin awal. Kredensialnya dari `config/platform.superadmin`
 * (SUPERADMIN_EMAIL / SUPERADMIN_PASSWORD di .env), bukan ditulis di kode:
 * password yang menempel di repo publik sama saja dengan tidak ada password.
 *
 * Idempoten — aman dijalankan lagi setelah deploy. Untuk menaikkan user yang
 * sudah ada tanpa seeder, pakai `php artisan pos:superadmin {email}`.
 */
class SuperadminSeeder extends Seeder
{
    public function run(SetSuperadmin $superadmin, EnsureUserHasStore $stores): void
    {
        $config = config('platform.superadmin');

        if (blank($config['password'])) {
            $this->command?->warn('SUPERADMIN_PASSWORD belum diisi — seeder superadmin dilewati.');

            return;
        }

        $user = User::query()->firstWhere('email', $config['email']);

        if ($user === null) {
            $user = User::query()->create([
                'name' => $config['name'],
                'email' => $config['email'],
                'password' => Hash::make($config['password']),
                'email_verified_at' => now(),
            ]);
        }
        // Password akun yang sudah ada sengaja TIDAK ditimpa: menjalankan
        // seeder lagi di server hidup tidak boleh diam-diam mengembalikan
        // password lama yang mungkin sudah bocor dan sudah diganti.

        // Superadmin tetap diberi toko sendiri: tanpa toko aktif, middleware
        // `store` menolak semua halaman area toko dengan 403 — termasuk
        // /dashboard yang jadi tujuan setelah login.
        $stores->handle($user);

        $superadmin->grant($user);

        $this->command?->info("Superadmin siap: {$user->email}");
    }
}
