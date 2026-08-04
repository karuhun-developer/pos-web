<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\AuthResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/v1')]
#[Middleware('auth:sanctum')]
class SessionController extends Controller
{
    /**
     * Profil user + daftar toko.
     */
    #[Get('auth/me')]
    public function me(Request $request): JsonResponse
    {
        return response()->json(AuthResponse::payload($request->user()));
    }

    /**
     * Cabut token yang dipakai request ini.
     */
    #[Post('auth/logout')]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(status: 204);
    }
}
