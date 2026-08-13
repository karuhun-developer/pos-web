<?php

namespace App\Http\Controllers\Web;

use App\Actions\Report\InventorySnapshot;
use App\Actions\Report\SalesSummary;
use App\Actions\Report\TopProducts;
use App\Http\Controllers\Controller;
use App\Models\CashierSession;
use App\Models\Product;
use App\Models\Sale;
use App\Support\DisplayTime;
use App\Support\ReportPeriod;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman pertama setelah login. Sengaja bukan versi kecil halaman laporan:
 * yang dijawab di sini cuma "hari ini bagaimana, dan apa yang perlu saya
 * urus sekarang" — sisanya biar dilihat di /laporan dengan rentang penuh.
 */
class DashboardController extends Controller
{
    /** Rentang tetap 14 hari untuk sparkline tren; bukan filter halaman. */
    private const TREND_DAYS = 14;

    public function __invoke(
        SalesSummary $summary,
        TopProducts $topProducts,
        InventorySnapshot $inventory,
    ): Response {
        $today = ReportPeriod::make('today', null, null);
        $fortnight = ReportPeriod::make('custom', DisplayTime::now()->subDays(self::TREND_DAYS - 1)->format('Y-m-d'), DisplayTime::now()->format('Y-m-d'));

        $canSeeReports = request()->user()->can('reports.view');

        return Inertia::render('Dashboard', [
            'today' => $summary->handle($today),
            'trend' => $summary->handle($fortnight)['trend'],
            // Kasir tidak boleh melihat laporan; tanpa penjaga ini dashboard
            // jadi pintu belakang ke angka yang di /laporan sudah ditutup.
            'top_products' => $canSeeReports ? $topProducts->handle($fortnight) : null,
            'inventory' => $canSeeReports ? $inventory->handle() : null,
            'can_see_reports' => $canSeeReports,
            'open_session' => $this->openSession(),
            'recent_sales' => $this->recentSales(),
            'counts' => [
                'products' => Product::query()->whereNull('deleted_at')->where('active', 1)->count(),
            ],
        ]);
    }

    /** @return array<string,mixed>|null */
    private function openSession(): ?array
    {
        $session = CashierSession::query()
            ->whereNull('deleted_at')
            ->where('status', 'open')
            ->orderByDesc('opened_at')
            ->first();

        if ($session === null) {
            return null;
        }

        return [
            'id' => $session->id,
            'opened_at' => DisplayTime::toLocal((int) $session->opened_at)->format('d/m H:i'),
            'opening_cash' => (int) $session->opening_cash,
            'cashier' => $session->opened_by,
        ];
    }

    /** @return list<array<string,mixed>> */
    private function recentSales(): array
    {
        return Sale::query()
            ->whereNull('deleted_at')
            ->orderByDesc('sold_at')
            ->limit(8)
            ->get(['id', 'number', 'total', 'payment_method', 'status', 'sold_at'])
            ->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'number' => $sale->number,
                'total' => (int) $sale->total,
                'payment_method' => $sale->payment_method,
                'status' => $sale->status,
                'sold_at' => DisplayTime::toLocal((int) $sale->sold_at)->format('d/m H:i'),
            ])
            ->all();
    }
}
