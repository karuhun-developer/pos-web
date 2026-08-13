<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Landing', [
            'googleEnabled' => filled(config('services.google.client_secret')),
            'androidDownload' => config('platform.android_download'),
        ]);
    }
}
