<?php

namespace App\Http\Controllers\Web;

use App\Actions\Platform\FetchAndroidRelease;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function index(FetchAndroidRelease $release): Response
    {
        return Inertia::render('Landing', [
            'release' => $release->handle(),
            'repos' => [
                'android' => config('platform.android_repository'),
                'web' => config('platform.repository'),
            ],
        ]);
    }
}
