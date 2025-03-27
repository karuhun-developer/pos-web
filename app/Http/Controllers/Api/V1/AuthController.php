<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request) {
        if (!$token = auth()->attempt($request->only('email', 'password'))) {
            return $this->responseWithError('Unauthorized', 401);
        }

        return $this->responseWithSuccess([
            'token' => auth()->user()->createToken('API Token')->plainTextToken,
            'token_type' => 'bearer',
        ]);
    }

    public function logout() {
        auth('api')->user()->tokens()->delete();

        return $this->responseWithSuccess(null, 'Successfully logged out');
    }
}
