<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\StoreContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Props yang dibagi ke SEMUA halaman Inertia. Bentuk user/stores sengaja
 * mengikuti App\Support\AuthResponse (kontrak yang sama dipakai API) supaya
 * web dan Android bicara tentang bentuk data yang sama.
 */
class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => ['name' => config('app.name')],
            /*
             * Wajib closure: Inertia\Middleware memanggil share() SEBELUM
             * $next($request), jadi kalau blok ini dievaluasi langsung ia
             * berjalan sebelum middleware `store` menyetel StoreContext —
             * hasilnya current_store null dan permissions kosong, dan seluruh
             * tombol tulis di UI hilang untuk pemilik toko sekalipun. Closure
             * baru diresolve saat halaman dirender, yaitu di dalam controller.
             */
            'auth' => function () use ($request) {
                /** @var User|null $user */
                $user = $request->user();

                return [
                    'user' => $user === null ? null : [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar_url' => $user->avatar_url,
                        'current_store_id' => $user->current_store_id,
                        'is_superadmin' => $user->isSuperadmin(),
                        // Permission ber-scope toko aktif; kosong kalau halaman ini
                        // memang tidak punya konteks toko (mis. area superadmin).
                        'permissions' => StoreContext::has()
                            ? $user->getAllPermissions()->pluck('name')->values()->all()
                            : [],
                    ],
                    'stores' => $user === null ? [] : $user->stores->map(fn ($store) => [
                        'id' => $store->id,
                        'name' => $store->name,
                        'role' => $store->pivot->role,
                    ])->values(),
                    'current_store' => $this->currentStore($user),
                ];
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    private function currentStore(?User $user): ?array
    {
        $store = StoreContext::get();

        if ($store === null || $user === null) {
            return null;
        }

        $membership = $user->stores->firstWhere('id', $store->id);

        return [
            'id' => $store->id,
            'name' => $store->name,
            'role' => $membership?->pivot->role ?? 'owner',
        ];
    }
}
