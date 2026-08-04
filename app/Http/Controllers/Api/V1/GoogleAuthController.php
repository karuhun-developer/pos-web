<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\AuthenticateWithGoogle;
use App\Http\Controllers\Controller;
use App\Http\Requests\GoogleAuthRequest;
use Illuminate\Http\JsonResponse;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/v1')]
class GoogleAuthController extends Controller
{
    /**
     * Login via Google ID token.
     *
     * Client mengirim `id_token` dari Google Sign-In; server memverifikasinya
     * ke Google lalu menerbitkan Sanctum bearer token.
     */
    #[Post('auth/google')]
    public function __invoke(GoogleAuthRequest $request, AuthenticateWithGoogle $action): JsonResponse
    {
        return response()->json(
            $action->handle($request->validated('id_token'))
        );
    }
}
