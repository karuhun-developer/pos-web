<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Sync\PullChanges;
use App\Http\Controllers\Controller;
use App\Http\Requests\SyncPullRequest;
use Illuminate\Http\JsonResponse;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/v1')]
#[Middleware(['auth:sanctum', 'store', 'permission:sync.pull'])]
class SyncPullController extends Controller
{
    /**
     * Tarik perubahan server untuk satu entity sejak `since` → PullResult
     * { entity, changes, cursor }.
     */
    #[Get('sync/pull')]
    public function __invoke(SyncPullRequest $request, PullChanges $action): JsonResponse
    {
        return response()->json(
            $action->handle($request->entity(), $request->since())
        );
    }
}
