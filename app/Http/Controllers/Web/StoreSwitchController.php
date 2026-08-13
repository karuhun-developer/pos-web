<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Pindah toko aktif. Toko yang diminta harus toko tempat user jadi anggota —
 * kalau tidak, 403 (bukan diam-diam diabaikan).
 */
class StoreSwitchController extends Controller
{
    public function __invoke(Request $request, Store $store): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->stores()->whereKey($store->getKey())->exists(), 403, 'Bukan anggota toko ini.');

        $user->forceFill(['current_store_id' => $store->id])->save();

        return back()->with('success', 'Toko aktif: '.$store->name);
    }
}
