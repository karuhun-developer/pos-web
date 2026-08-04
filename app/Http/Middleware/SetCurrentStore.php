<?php

namespace App\Http\Middleware;

use App\Support\StoreContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menetapkan toko aktif untuk request: dari header X-Store-Id (bila user anggota)
 * atau current_store_id user. Meng-set StoreContext (dipakai StoreScope) dan team
 * id spatie (dipakai cek permission per-toko). Wajib setelah auth:sanctum.
 */
class SetCurrentStore
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $requested = $request->header('X-Store-Id');

        $store = $requested
            ? $user?->stores()->whereKey($requested)->first()
            : ($user?->currentStore ?? $user?->stores()->first());

        // Tanpa toko aktif yang valid (mis. minta store yang bukan miliknya) →
        // tolak: endpoint sync selalu butuh konteks toko.
        abort_if($store === null, 403, 'Toko aktif tidak ditemukan.');

        StoreContext::set($store);
        // Set eksplisit tiap request agar tidak ada kebocoran team id antar request.
        app(PermissionRegistrar::class)->setPermissionsTeamId($store->id);

        return $next($request);
    }

    public function terminate(): void
    {
        StoreContext::clear();
    }
}
