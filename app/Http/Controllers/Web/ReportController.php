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
use App\Support\ReportPeriod;
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
    public function index(
        Request $request,
        SalesSummary $summary,
        HourlyHeatmap $heatmap,
        TopProducts $topProducts,
        PaymentMix $paymentMix,
        CategoryMargin $categoryMargin,
        CashflowDaily $cashflow,
        SessionVariance $sessions,
        InventorySnapshot $inventory,
    ): Response {
        $this->authorize('viewAny', Sale::class);

        $period = ReportPeriod::make(
            $request->query('preset'),
            $request->query('from'),
            $request->query('to'),
        );

        return Inertia::render('Reports/Index', [
            'period' => $period->toArray(),
            'summary' => $summary->handle($period),
            'heatmap' => $heatmap->handle($period),
            'top_products' => $topProducts->handle($period),
            'payment_mix' => $paymentMix->handle($period),
            'category_margin' => $categoryMargin->handle($period),
            'cashflow' => $cashflow->handle($period),
            'sessions' => $sessions->handle($period),
            'inventory' => $inventory->handle(),
        ]);
    }
}
