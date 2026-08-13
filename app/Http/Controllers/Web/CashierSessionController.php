<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CashierSession;
use App\Models\Sale;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Riwayat sesi kasir — hanya baca. Sesi dibuka & ditutup di perangkat kasir,
 * jadi web tidak menyediakan aksi tulis apa pun di sini.
 */
class CashierSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CashierSession::class);

        $sessions = CashierSession::query()
            ->whereNull('deleted_at')
            ->when($request->query('status', 'all') !== 'all', fn ($q) => $q->where('status', $request->query('status')))
            ->orderByDesc('opened_at')
            ->paginate(20)
            ->withQueryString();

        // Omzet per sesi dihitung sekali untuk halaman ini saja (bukan N+1
        // per baris).
        $revenue = Sale::query()
            ->whereNull('deleted_at')
            ->where('status', 'completed')
            ->whereIn('session_id', $sessions->pluck('id'))
            ->groupBy('session_id')
            ->selectRaw('session_id, count(*) as orders, coalesce(sum(total), 0) as revenue')
            ->get()
            ->keyBy('session_id');

        $sessions->getCollection()->transform(function (CashierSession $session) use ($revenue) {
            $row = $revenue->get($session->id);
            $session->setAttribute('orders_count', (int) ($row->orders ?? 0));
            $session->setAttribute('revenue', (int) ($row->revenue ?? 0));

            return $session;
        });

        return Inertia::render('Sessions/Index', [
            'sessions' => $sessions,
            'filters' => ['status' => $request->query('status', 'all')],
        ]);
    }
}
