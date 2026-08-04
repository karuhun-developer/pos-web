<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\LoginWithPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/v1')]
class LoginController extends Controller
{
    /**
     * Login email/password (dev & test).
     */
    #[Post('auth/login')]
    public function __invoke(LoginRequest $request, LoginWithPassword $action): JsonResponse
    {
        return response()->json(
            $action->handle($request->validated('email'), $request->validated('password'))
        );
    }
}
