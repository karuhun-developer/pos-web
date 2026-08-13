<?php

namespace App\Http\Controllers\Web;

use App\Actions\Cashflow\DeleteEntry;
use App\Actions\Cashflow\SaveEntry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CashflowEntryRequest;
use App\Models\CashflowCategory;
use App\Models\CashflowEntry;
use App\Support\DisplayTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CashflowController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CashflowEntry::class);

        $filters = [
            'direction' => $request->query('direction', 'all'),
            'category' => $request->query('category'),
            'source' => $request->query('source', 'all'),
            'from' => $request->query('from', DisplayTime::now()->startOfMonth()->format('Y-m-d')),
            'to' => $request->query('to', DisplayTime::now()->format('Y-m-d')),
        ];

        $query = CashflowEntry::query()
            ->whereNull('deleted_at')
            ->where('occurred_at', '>=', DisplayTime::startOfDayMs($filters['from']))
            ->where('occurred_at', '<=', DisplayTime::endOfDayMs($filters['to']))
            ->when($filters['direction'] !== 'all', fn ($q) => $q->where('direction', $filters['direction']))
            ->when($filters['category'], fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['source'] !== 'all', fn ($q) => $q->where('source', $filters['source']));

        $totals = (clone $query)
            ->selectRaw("coalesce(sum(case when direction = 'debit' then amount end), 0) as income")
            ->selectRaw("coalesce(sum(case when direction = 'credit' then amount end), 0) as expense")
            ->first();

        return Inertia::render('Cashflow/Index', [
            'entries' => $query->orderByDesc('occurred_at')->paginate(25)->withQueryString(),
            'categories' => $this->categories(),
            'filters' => $filters,
            'totals' => [
                'income' => (int) ($totals->income ?? 0),
                'expense' => (int) ($totals->expense ?? 0),
                'net' => (int) ($totals->income ?? 0) - (int) ($totals->expense ?? 0),
            ],
        ]);
    }

    public function store(CashflowEntryRequest $request, SaveEntry $save): RedirectResponse
    {
        $this->authorize('create', CashflowEntry::class);

        $save->handle($this->payload($request));

        return back()->with('success', 'Catatan kas ditambahkan.');
    }

    public function update(CashflowEntryRequest $request, CashflowEntry $entry, SaveEntry $save): RedirectResponse
    {
        $this->authorize('update', $entry);

        // Entri hasil penjualan adalah cerminan transaksi, bukan catatan bebas;
        // mengubahnya di sini akan membuat ledger dan struk tidak lagi cocok.
        abort_if($entry->source === 'sale', 403, 'Entri dari penjualan tidak bisa diubah manual.');

        $save->handle($this->payload($request), $entry);

        return back()->with('success', 'Catatan kas diperbarui.');
    }

    public function destroy(CashflowEntry $entry, DeleteEntry $delete): RedirectResponse
    {
        $this->authorize('delete', $entry);

        abort_if($entry->source === 'sale', 403, 'Entri dari penjualan tidak bisa dihapus manual.');

        $delete->handle($entry);

        return back()->with('success', 'Catatan kas dihapus.');
    }

    /**
     * Form mengirim tanggal saja; jam-nya dipatok ke waktu sekarang supaya
     * urutan entri di hari yang sama tetap masuk akal.
     *
     * @return array<string,mixed>
     */
    private function payload(CashflowEntryRequest $request): array
    {
        $data = $request->validated();
        $now = DisplayTime::now();

        $data['occurred_at'] = DisplayTime::startOfDayMs($data['occurred_on'])
            + ($now->hour * 3600 + $now->minute * 60 + $now->second) * 1000;

        return $data;
    }

    /** @return Collection<int,CashflowCategory> */
    private function categories()
    {
        return CashflowCategory::query()
            ->whereNull('deleted_at')
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'is_system', 'sort_order']);
    }
}
