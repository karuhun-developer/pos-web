<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Sync\PushChanges;
use App\Http\Controllers\Controller;
use App\Http\Requests\SyncPushRequest;
use Illuminate\Http\JsonResponse;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/v1')]
#[Middleware(['auth:sanctum', 'store', 'permission:sync.push'])]
class SyncPushController extends Controller
{
    /**
     * Kirim perubahan lokal (batch ChangeEnvelope) → PushResult { acked, rejected }.
     */
    #[Post('sync/push')]
    public function __invoke(SyncPushRequest $request, PushChanges $action): JsonResponse
    {
        $result = $action->handle(
            $request->validated('changes'),
            $request->header('X-Device-Id'),
        );

        return response()->json($result);
    }
}
