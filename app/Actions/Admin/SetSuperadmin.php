<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Once;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Support\Config;

/**
 * Memberi/mencabut role platform `superadmin`.
 *
 * Kenapa pivotnya ditulis langsung, bukan lewat assignRole()?
 *
 * Karena mode teams spatie menjadikan `team_id` bagian dari PRIMARY KEY tabel
 * model_has_roles — kolomnya NOT NULL. Role ini justru hidup di luar toko
 * manapun, jadi assignRole() dengan team id null selalu gagal di level basis
 * data. Yang dipakai di sini adalah sentinel PLATFORM_TEAM: id toko selalu ≥ 1,
 * jadi 0 tidak akan pernah bentrok dengan toko sungguhan.
 *
 * Konsekuensinya, superadmin TIDAK terbaca lewat relasi roles() milik spatie
 * (selalu difilter team aktif) — dan memang tidak dipakai begitu:
 * User::isSuperadmin() menanyakan pivotnya langsung, dan Gate::before
 * meloloskan semua izin sebelum spatie sempat ditanya.
 */
class SetSuperadmin
{
    /** Toko punya id ≥ 1; 0 dipakai sebagai penanda "lintas platform". */
    public const PLATFORM_TEAM = 0;

    public function grant(User $user): void
    {
        DB::table(Config::modelHasRolesTable())->updateOrInsert([
            'role_id' => $this->roleId(),
            'model_type' => $user->getMorphClass(),
            Config::morphKey() => $user->getKey(),
            Config::teamForeignKey() => self::PLATFORM_TEAM,
        ]);

        $this->forget();
    }

    public function revoke(User $user): void
    {
        DB::table(Config::modelHasRolesTable())
            ->where('role_id', $this->roleId())
            ->where('model_type', $user->getMorphClass())
            ->where(Config::morphKey(), $user->getKey())
            ->delete();

        $this->forget();
    }

    private function roleId(): int|string
    {
        return Config::roleModel()::query()
            ->where('name', User::SUPERADMIN_ROLE)
            ->where('guard_name', 'web')
            ->firstOrFail()
            ->getKey();
    }

    /**
     * isSuperadmin() dimemoize dengan once(); tanpa flush, kode setelah ini di
     * request yang sama masih membaca jawaban lama.
     */
    private function forget(): void
    {
        Once::flush();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
