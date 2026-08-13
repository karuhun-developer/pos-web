<?php

namespace App\Actions\Report;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\ReportPeriod;

/**
 * HPP vs margin per kategori — batang bertumpuk (part-to-whole): tinggi total
 * batang = omzet kategori, terbagi jadi modal dan margin.
 *
 * Modal memakai harga modal produk saat ini (tidak di-snapshot di sale_items),
 * jadi angkanya estimasi — sama seperti laba kotor di KPI.
 */
class CategoryMargin
{
    /** @return array<string,mixed> */
    public function handle(ReportPeriod $period): array
    {
        $saleIds = Sale::query()
            ->whereNull('deleted_at')
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$period->startMs(), $period->endMs()])
            ->pluck('id');

        if ($saleIds->isEmpty()) {
            return ['rows' => []];
        }

        $items = SaleItem::query()
            ->whereNull('deleted_at')
            ->whereIn('sale_id', $saleIds->all())
            ->groupBy('product_id')
            ->selectRaw('product_id, sum(qty) as qty, sum(line_total) as revenue')
            ->get();

        $products = Product::query()
            ->whereIn('id', $items->pluck('product_id')->filter()->all())
            ->get(['id', 'category_id', 'cost'])
            ->keyBy('id');

        $categoryNames = Category::query()
            ->whereNull('deleted_at')
            ->pluck('name', 'id');

        /** @var array<string,array{name:string,revenue:int,cost:int}> $buckets */
        $buckets = [];

        foreach ($items as $item) {
            $product = $products->get($item->product_id);
            $key = $product?->category_id ?? '_none';
            $name = $key === '_none'
                ? 'Tanpa kategori'
                : ($categoryNames[$key] ?? 'Kategori terhapus');

            $buckets[$key] ??= ['name' => $name, 'revenue' => 0, 'cost' => 0];
            $buckets[$key]['revenue'] += (int) $item->revenue;
            $buckets[$key]['cost'] += (int) ($product?->cost ?? 0) * (int) $item->qty;
        }

        $rows = collect($buckets)
            ->map(fn (array $bucket) => [
                'name' => $bucket['name'],
                'revenue' => $bucket['revenue'],
                'cost' => $bucket['cost'],
                'margin' => $bucket['revenue'] - $bucket['cost'],
                'margin_pct' => $bucket['revenue'] > 0
                    ? round((($bucket['revenue'] - $bucket['cost']) / $bucket['revenue']) * 100, 1)
                    : 0.0,
            ])
            ->sortByDesc('revenue')
            ->values();

        return ['rows' => $rows->all()];
    }
}
