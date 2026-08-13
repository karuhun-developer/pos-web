<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreSettingsRequest;
use App\Models\Store;
use App\Support\StoreContext;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StoreSettingsController extends Controller
{
    public function edit(): Response
    {
        $store = $this->currentStore();
        $this->authorize('view', $store);

        return Inertia::render('Store/Edit', [
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'owner_id' => $store->owner_id,
                'created_at' => $store->created_at,
            ],
            'members' => $store->users()->get()->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'role' => $user->pivot->role,
                'is_owner' => $user->id === $store->owner_id,
            ]),
            'can_update' => request()->user()->can('update', $store),
        ]);
    }

    public function update(StoreSettingsRequest $request): RedirectResponse
    {
        $store = $this->currentStore();
        $this->authorize('update', $store);

        $store->forceFill(['name' => $request->validated('name')])->save();

        return back()->with('success', 'Pengaturan toko disimpan.');
    }

    /** Toko aktif dijamin ada oleh middleware 'store' (SetCurrentStore). */
    private function currentStore(): Store
    {
        return StoreContext::get() ?? abort(403, 'Tidak ada toko aktif.');
    }
}
