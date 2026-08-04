<?php

namespace App\Actions\Store;

use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Membuat outlet/toko baru dan menjadikan user sebagai 'owner' (pivot +
 * role spatie ber-scope team=store). Tidak mengubah toko aktif user — klien
 * yang memutuskan berpindah. Pola sama dengan EnsureUserHasStore.
 */
class CreateStore
{
    public function handle(User $user, string $name): Store
    {
        return DB::transaction(function () use ($user, $name) {
            $store = Store::create([
                'owner_id' => $user->id,
                'name' => $name,
            ]);

            $user->stores()->attach($store->id, ['role' => 'owner']);

            app(PermissionRegistrar::class)->setPermissionsTeamId($store->id);
            $user->assignRole('owner');

            return $store;
        });
    }
}
