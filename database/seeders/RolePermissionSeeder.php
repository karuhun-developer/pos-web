<?php

namespace Database\Seeders;

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
    public const PERMISSIONS = [
        'sync.push',
        'sync.pull',
        'catalog.manage',
        'reports.view',
        'cashier.session',
    ];

    public const ROLES = [
        'owner' => self::PERMISSIONS, // akses penuh
        'cashier' => ['sync.push', 'sync.pull', 'cashier.session'],
    ];

    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
        // Role & permission global (bukan milik satu toko).
        $registrar->setPermissionsTeamId(null);

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name);
        }

        foreach (self::ROLES as $role => $permissions) {
            Role::findOrCreate($role)->syncPermissions($permissions);
        }

        $registrar->forgetCachedPermissions();
    }
}
