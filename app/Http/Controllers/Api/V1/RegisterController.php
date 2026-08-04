<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\RegisterWithPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/v1')]
class RegisterController extends Controller
{
    /**
     * Registrasi akun baru email/password. Membuat user + toko default (owner)
     * dan menerbitkan Sanctum token.
     */
    #[Post('auth/register')]
    public function __invoke(RegisterRequest $request, RegisterWithPassword $action): JsonResponse
    {
        return response()->json(
            $action->handle(
                $request->validated('name'),
                $request->validated('email'),
                $request->validated('password'),
            ),
            201,
        );
    }
}
