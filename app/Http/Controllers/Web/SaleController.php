<?php

namespace App\Http\Controllers\Web;

use App\Actions\Sale\VoidSale;
use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\DisplayTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Sale::class);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status', 'all'),
            'method' => $request->query('method'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        $query = Sale::query()
            ->whereNull('deleted_at')
            ->when($filters['q'] !== '', fn ($q) => $q->where('number', 'like', "%{$filters['q']}%"))
            ->when($filters['status'] !== 'all', fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['method'], fn ($q, $method) => $q->where('payment_method', $method));

        if ($filters['from']) {
            $query->where('sold_at', '>=', DisplayTime::startOfDayMs($filters['from']));
        }
        if ($filters['to']) {
            $query->where('sold_at', '<=', DisplayTime::endOfDayMs($filters['to']));
        }

        // Ringkasan dihitung dari query yang SAMA dengan daftarnya (sebelum
        // paginate) supaya angka di atas tabel selalu cocok dengan filternya.
        $summary = (clone $query)
            ->where('status', 'completed')
            ->selectRaw('count(*) as orders, coalesce(sum(total), 0) as revenue')
            ->first();

        return Inertia::render('Sales/Index', [
            'sales' => $query->orderByDesc('sold_at')->paginate(25)->withQueryString(),
            'filters' => $filters,
            'summary' => [
                'orders' => (int) ($summary->orders ?? 0),
                'revenue' => (int) ($summary->revenue ?? 0),
            ],
            'payment_methods' => Sale::query()
                ->whereNull('deleted_at')
                ->distinct()
                ->orderBy('payment_method')
                ->pluck('payment_method'),
        ]);
    }

    public function show(Sale $sale): Response
    {
        $this->authorize('view', $sale);

        return Inertia::render('Sales/Show', [
            'sale' => $sale,
            'items' => SaleItem::query()
                ->where('sale_id', $sale->id)
                ->whereNull('deleted_at')
                ->get(),
            'can_void' => $sale->status !== 'void'
                && request()->user()->can('void', $sale),
        ]);
    }

    public function void(Sale $sale, VoidSale $voidSale): RedirectResponse
    {
        $this->authorize('void', $sale);

        $voidSale->handle($sale);

        return back()->with('success', "Transaksi {$sale->number} dibatalkan.");
    }
}
