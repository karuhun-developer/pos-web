<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Admin\SyncActivity;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Log sync ringkas: perangkat mana yang terakhir menulis, dan entity mana yang
 * paling belakangan bergerak. Dipakai untuk menjawab "kenapa data toko X tidak
 * muncul di kasir" tanpa harus membuka database.
 */
class AdminSyncController extends Controller
{
    public function __invoke(SyncActivity $activity): Response
    {
        return Inertia::render('Admin/Sync', $activity->handle());
    }
}
