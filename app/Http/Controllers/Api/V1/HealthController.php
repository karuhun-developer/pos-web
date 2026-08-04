<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/v1')]
class HealthController extends Controller
{
    /**
     * Health check (tanpa auth).
     */
    #[Get('health')]
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'time' => (int) (microtime(true) * 1000),
            'version' => 'v1',
        ]);
    }
}
