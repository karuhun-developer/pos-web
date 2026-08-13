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

        // Rentang bawaan sama dengan Arus Kas: awal bulan berjalan → hari ini.
        // Tanpa default, halaman ini membuka seluruh riwayat toko sekaligus —
        // dan ringkasan di atas tabel jadi angka sepanjang masa, bukan periode
        // yang sedang dilihat.
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status', 'all'),
            'method' => $request->query('method'),
            'from' => $request->query('from', DisplayTime::now()->startOfMonth()->format('Y-m-d')),
            'to' => $request->query('to', DisplayTime::now()->format('Y-m-d')),
        ];

        $query = Sale::query()
            ->whereNull('deleted_at')
            ->when($filters['q'] !== '', fn ($q) => $q->where('number', 'like', "%{$filters['q']}%"))
            ->when($filters['status'] !== 'all', fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['method'], fn ($q, $method) => $q->where('payment_method', $method));

        // Query string kosong (?from=) berarti "buka batasnya", bukan "pakai
        // default lagi" — kalau tidak, filter tanggal tidak bisa dihapus.
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
