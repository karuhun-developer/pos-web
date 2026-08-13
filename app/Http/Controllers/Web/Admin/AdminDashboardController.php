<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Admin\PlatformOverview;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(PlatformOverview $overview): Response
    {
        return Inertia::render('Admin/Dashboard', $overview->handle());
    }
}
