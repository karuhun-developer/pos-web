<?php

namespace App\Actions\Auth;

use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Menjamin user punya minimal satu toko. Saat toko pertama dibuat, user jadi
 * 'owner' (pivot + role spatie ber-scope team=store) dan current_store_id di-set.
 */
class EnsureUserHasStore
{
    public function handle(User $user): Store
    {
        if ($user->stores()->exists()) {
            return $user->currentStore ?? $user->stores()->first();
        }

        return DB::transaction(function () use ($user) {
            $store = Store::create([
                'owner_id' => $user->id,
                'name' => 'Toko '.Str::of($user->name)->before(' ')->trim()->whenEmpty(fn () => Str::of('Saya')),
            ]);

            $user->stores()->attach($store->id, ['role' => 'owner']);

            app(PermissionRegistrar::class)->setPermissionsTeamId($store->id);
            $user->assignRole('owner');

            $user->forceFill(['current_store_id' => $store->id])->save();

            return $store;
        });
    }
}
