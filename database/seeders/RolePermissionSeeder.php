<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Membuat permission + role global (team_id null) yang bisa dipakai lintas toko;
 * assignment role ke user tetap ber-scope per toko (team). Lihat
 * docs/features/rbac-stores.md.
 */
class RolePermissionSeeder extends Seeder
{
    /** Permission di dalam toko; di-assign lewat role ber-scope team. */
    public const PERMISSIONS = [
        'sync.push',
        'sync.pull',
        'catalog.manage',
        'reports.view',
        'cashier.session',
        'cashflow.manage',
        'sale.void',
    ];

    /**
     * Permission platform (lintas toko) — hanya untuk role superadmin. Dipisah
     * supaya tidak pernah ikut ter-assign ke owner sebuah toko.
     */
    public const PLATFORM_PERMISSIONS = [
        'platform.manage',
        'donation.manage',
    ];

    public const ROLES = [
        'owner' => self::PERMISSIONS, // akses penuh di dalam tokonya
        'cashier' => ['sync.push', 'sync.pull', 'cashier.session', 'cashflow.manage'],
        User::SUPERADMIN_ROLE => self::PLATFORM_PERMISSIONS,
    ];

    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
        // Role & permission global (bukan milik satu toko).
        $registrar->setPermissionsTeamId(null);

        foreach ([...self::PERMISSIONS, ...self::PLATFORM_PERMISSIONS] as $name) {
            Permission::findOrCreate($name);
        }

        foreach (self::ROLES as $role => $permissions) {
            Role::findOrCreate($role)->syncPermissions($permissions);
        }

        $registrar->forgetCachedPermissions();
    }
}
