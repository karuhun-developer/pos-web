<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashflowEntry;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Scopes\StoreScope;
use App\Models\Store;
use App\Models\SyncModel;
use App\Models\User;
use App\Support\DisplayTime;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Daftar toko lintas tenant. Semua query entity sync di sini melepas StoreScope
 * secara eksplisit — lihat alasannya di PlatformOverview.
 */
class AdminStoreController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $stores = Store::query()
            ->with('owner:id,name,email')
            ->withCount('users')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Agregat dihitung sekali untuk halaman ini, bukan per baris.
        $ids = $stores->pluck('id');
        $products = $this->countBy(Product::class, $ids);
        $sales = Sale::withoutGlobalScope(StoreScope::class)
            ->whereNull('deleted_at')
            ->where('status', 'completed')
            ->whereIn('store_id', $ids)
            ->groupBy('store_id')
            ->selectRaw('store_id, count(*) as orders, coalesce(sum(total), 0) as revenue')
            ->get()
            ->keyBy('store_id');

        $stores->getCollection()->transform(fn (Store $store) => [
            'id' => $store->id,
            'name' => $store->name,
            'owner' => $store->owner?->only(['id', 'name', 'email']),
            'members' => $store->users_count,
            'products' => (int) ($products[$store->id] ?? 0),
            'orders' => (int) ($sales[$store->id]->orders ?? 0),
            'revenue' => (int) ($sales[$store->id]->revenue ?? 0),
            'created_at' => $store->created_at?->toIso8601String(),
        ]);

        return Inertia::render('Admin/Stores/Index', [
            'stores' => $stores,
            'filters' => ['q' => $search],
        ]);
    }

    public function show(Store $store): Response
    {
        $sales = Sale::withoutGlobalScope(StoreScope::class)
            ->whereNull('deleted_at')
            ->where('store_id', $store->id);

        $recent = (clone $sales)
            ->orderByDesc('sold_at')
            ->limit(10)
            ->get(['id', 'number', 'total', 'status', 'payment_method', 'sold_at'])
            ->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'number' => $sale->number,
                'total' => (int) $sale->total,
                'status' => $sale->status,
                'payment_method' => $sale->payment_method,
                'sold_at' => (int) $sale->sold_at,
            ]);

        return Inertia::render('Admin/Stores/Show', [
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'owner' => $store->owner?->only(['id', 'name', 'email']),
                'created_at' => $store->created_at?->toIso8601String(),
            ],
            'kpi' => [
                'products' => Product::withoutGlobalScope(StoreScope::class)
                    ->whereNull('deleted_at')->where('store_id', $store->id)->count(),
                'orders' => (clone $sales)->where('status', 'completed')->count(),
                'revenue' => (int) (clone $sales)->where('status', 'completed')->sum('total'),
                'cashflow_entries' => CashflowEntry::withoutGlobalScope(StoreScope::class)
                    ->whereNull('deleted_at')->where('store_id', $store->id)->count(),
            ],
            'last_activity' => $this->lastActivity($store),
            'members' => $store->users()->get()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role,
                'is_superadmin' => $user->isSuperadmin(),
            ])->all(),
            'recent_sales' => $recent,
        ]);
    }

    /**
     * Kapan terakhir toko ini "hidup". Dibaca dari updated_at (epoch ms) row
     * terbaru — sinyal yang sama dengan yang dipakai sinkronisasi.
     */
    private function lastActivity(Store $store): ?string
    {
        $latest = Sale::withoutGlobalScope(StoreScope::class)
            ->where('store_id', $store->id)
            ->max('updated_at');

        return $latest ? DisplayTime::toLocal((int) $latest)->toIso8601String() : null;
    }

    /**
     * @param  class-string<SyncModel>  $model
     * @param  Collection<int,int>  $storeIds
     * @return Collection<int,int>
     */
    private function countBy(string $model, Collection $storeIds): Collection
    {
        return $model::withoutGlobalScope(StoreScope::class)
            ->whereNull('deleted_at')
            ->whereIn('store_id', $storeIds)
            ->groupBy('store_id')
            ->selectRaw('store_id, count(*) as total')
            ->pluck('total', 'store_id');
    }
}
