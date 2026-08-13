<?php

namespace App\Http\Controllers\Web;

use App\Actions\Report\CashflowDaily;
use App\Actions\Report\CategoryMargin;
use App\Actions\Report\HourlyHeatmap;
use App\Actions\Report\InventorySnapshot;
use App\Actions\Report\PaymentMix;
use App\Actions\Report\SalesSummary;
use App\Actions\Report\SessionVariance;
use App\Actions\Report\TopProducts;
use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Support\DisplayTime;
use App\Support\ReportPeriod;
use App\Support\StoreContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Satu halaman, satu rentang waktu. Semua panel dihitung dari ReportPeriod
 * yang sama supaya angka di KPI, grafik, dan tabel tidak pernah saling
 * bertentangan.
 */
class ReportController extends Controller
{
    public function __construct(
        private readonly SalesSummary $summary,
        private readonly HourlyHeatmap $heatmap,
        private readonly TopProducts $topProducts,
        private readonly PaymentMix $paymentMix,
        private readonly CategoryMargin $categoryMargin,
        private readonly CashflowDaily $cashflow,
        private readonly SessionVariance $sessions,
        private readonly InventorySnapshot $inventory,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewReports', Sale::class);

        return Inertia::render('Reports/Index', $this->payload($this->period($request)));
    }

    /**
     * Versi cetak: HTML biasa, bukan Inertia — chart diganti tabel, dan
     * "simpan sebagai PDF" diserahkan ke dialog cetak browser. Sengaja tanpa
     * pustaka PDF baru: satu berkas Blade jauh lebih murah dirawat daripada
     * renderer yang punya font & layout engine sendiri.
     */
    public function print(Request $request): View
    {
        $this->authorize('viewReports', Sale::class);

        $period = $this->period($request);

        return view('reports.print', [
            ...$this->payload($period),
            'store' => StoreContext::get(),
            'printed_at' => DisplayTime::now()->format('d/m/Y H:i'),
        ]);
    }

    private function period(Request $request): ReportPeriod
    {
        return ReportPeriod::make(
            $request->query('preset'),
            $request->query('from'),
            $request->query('to'),
        );
    }

    /** @return array<string,mixed> */
    private function payload(ReportPeriod $period): array
    {
        return [
            'period' => $period->toArray(),
            'summary' => $this->summary->handle($period),
            'heatmap' => $this->heatmap->handle($period),
            'top_products' => $this->topProducts->handle($period),
            'payment_mix' => $this->paymentMix->handle($period),
            'category_margin' => $this->categoryMargin->handle($period),
            'cashflow' => $this->cashflow->handle($period),
            'sessions' => $this->sessions->handle($period),
            'inventory' => $this->inventory->handle(),
        ];
    }
}
