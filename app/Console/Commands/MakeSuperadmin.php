<?php

namespace App\Console\Commands;

use App\Actions\Admin\SetSuperadmin;
use App\Models\User;
use Illuminate\Console\Command;

class MakeSuperadmin extends Command
{
    protected $signature = 'pos:superadmin {email} {--revoke : Cabut akses superadmin}';

    protected $description = 'Jadikan (atau cabut) seorang user sebagai superadmin platform';

    public function handle(SetSuperadmin $superadmin): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error("User dengan email {$this->argument('email')} tidak ditemukan.");

            return self::FAILURE;
        }

        // Penulisan pivotnya diserahkan ke SetSuperadmin: role ini di luar
        // model team spatie, jadi ada sedikit trik yang tidak boleh berbeda
        // antara command ini dan panel admin.
        if ($this->option('revoke')) {
            $superadmin->revoke($user);
            $this->info("{$user->email} bukan superadmin lagi.");

            return self::SUCCESS;
        }

        $superadmin->grant($user);
        $this->info("{$user->email} sekarang superadmin.");

        return self::SUCCESS;
    }
}
