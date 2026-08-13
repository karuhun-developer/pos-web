<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman "Tentang": versi yang sedang berjalan, keterangan singkat, dan tautan
 * ke repo. Sengaja terbuka untuk publik — orang yang menimbang mau memakai POS
 * Pro perlu bisa melihat kodenya sebelum bikin akun.
 */
class AboutController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('About', [
            'app' => [
                'version' => config('platform.version'),
                'repository' => config('platform.repository'),
                'android_repository' => config('platform.android_repository'),
            ],
        ]);
    }
}
