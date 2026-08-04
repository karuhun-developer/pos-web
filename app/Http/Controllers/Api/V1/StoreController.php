<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
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
        $stores = $request->user()->stores->map(fn ($store) => [
            'id' => $store->id,
            'name' => $store->name,
            'role' => $store->pivot->role,
        ])->values();

        return response()->json(['stores' => $stores]);
    }
}
