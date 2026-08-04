<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Store\CreateStore;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/v1')]
#[Middleware('auth:sanctum')]
class StoreController extends Controller
{
    /**
     * Daftar toko milik/anggota user.
     */
    #[Get('stores')]
    public function index(Request $request): JsonResponse
    {
        return response()->json(['stores' => $this->listFor($request->user())]);
    }

    /**
     * Buat outlet baru — user otomatis jadi owner. Tidak mengubah toko aktif.
     */
    #[Post('stores')]
    public function store(StoreStoreRequest $request, CreateStore $action): JsonResponse
    {
        $user = $request->user();
        $store = $action->handle($user, $request->validated('name'));

        return response()->json([
            'store' => ['id' => $store->id, 'name' => $store->name, 'role' => 'owner'],
            'stores' => $this->listFor($user->fresh()),
        ], 201);
    }

    /**
     * Ubah nama outlet. Hanya owner outlet tersebut yang boleh.
     */
    #[Patch('stores/{store}')]
    public function update(UpdateStoreRequest $request, Store $store): JsonResponse
    {
        $membership = $request->user()->stores()->whereKey($store->getKey())->first();
        abort_if($membership === null, 403, 'Bukan anggota toko ini.');
        abort_unless($membership->pivot->role === 'owner', 403, 'Hanya pemilik yang bisa mengubah outlet.');

        $store->update(['name' => $request->validated('name')]);

        return response()->json([
            'store' => ['id' => $store->id, 'name' => $store->name, 'role' => $membership->pivot->role],
        ]);
    }

    /** @return Collection<int,array<string,mixed>> */
    private function listFor(User $user): Collection
    {
        return $user->stores->map(fn ($store) => [
            'id' => $store->id,
            'name' => $store->name,
            'role' => $store->pivot->role,
        ])->values();
    }
}
