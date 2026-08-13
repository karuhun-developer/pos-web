<?php

namespace App\Actions\Admin;

use App\Models\Donation;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Scopes\StoreScope;
use App\Models\Store;
use App\Models\User;
use App\Support\DisplayTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Angka-angka lintas toko untuk halaman ringkasan superadmin.
 *
 * Setiap query entity sync di sini WAJIB melepas StoreScope secara eksplisit.
 * Scope itu fail-open (tanpa toko aktif ia tidak membatasi apa pun), jadi
 * tanpa `withoutGlobalScope` hasilnya kebetulan benar — dan "kebetulan benar"
 * akan diam-diam berubah jadi salah begitu ada yang menyetel toko aktif.
 *
 * Pemotongan bulan dilakukan di PHP dengan zona tampilan, sama seperti semua
 * laporan lain: tidak ada SQL khusus driver untuk epoch ms.
 */
class PlatformOverview
{
    private const MONTHS = 12;

    /** @return array<string,mixed> */
    public function handle(): array
    {
        $months = $this->monthKeys();

        return [
            'kpi' => $this->kpi(),
            'growth' => $this->growth($months),
            'revenue' => $this->revenue($months),
            'donations' => $this->donations($months),
            'recent_stores' => $this->recentStores(),
            'recent_donations' => $this->recentDonations(),
        ];
    }

    /** @return array<string,int> */
    private function kpi(): array
    {
        $sales = Sale::withoutGlobalScope(StoreScope::class)
            ->whereNull('deleted_at')
            ->where('status', 'completed');

        return [
            'stores' => Store::count(),
            'users' => User::count(),
            'products' => Product::withoutGlobalScope(StoreScope::class)->whereNull('deleted_at')->count(),
            'orders' => (clone $sales)->count(),
            'revenue' => (int) (clone $sales)->sum('total'),
            'donation_amount' => (int) Donation::whereIn('status', ['recorded', 'paid'])->sum('amount'),
            'donation_count' => Donation::whereIn('status', ['recorded', 'paid'])->count(),
        ];
    }

    /**
     * Toko & pengguna baru per bulan. Dua seri, satuannya sama (jumlah) —
     * jadi cukup satu sumbu, tidak perlu (dan tidak boleh) dual-axis.
     *
     * @param  list<string>  $months
     * @return array<string,mixed>
     */
    private function growth(array $months): array
    {
        return [
            'months' => $months,
            'stores' => $this->countByMonth(Store::query()->pluck('created_at'), $months),
            'users' => $this->countByMonth(User::query()->pluck('created_at'), $months),
        ];
    }

    /**
     * Omzet seluruh platform per bulan. Dibaca lewat cursor supaya jumlah
     * transaksi yang besar tidak dimuat sekaligus ke memori.
     *
     * @param  list<string>  $months
     * @return array<string,mixed>
     */
    private function revenue(array $months): array
    {
        $buckets = array_fill_keys($months, 0);
        $since = CarbonImmutable::parse($months[0].'-01', DisplayTime::zone())->startOfMonth()->getTimestampMs();

        Sale::withoutGlobalScope(StoreScope::class)
            ->whereNull('deleted_at')
            ->where('status', 'completed')
            ->where('sold_at', '>=', $since)
            ->select(['sold_at', 'total'])
            ->cursor()
            ->each(function (Sale $sale) use (&$buckets) {
                $key = DisplayTime::toLocal((int) $sale->sold_at)->format('Y-m');

                if (array_key_exists($key, $buckets)) {
                    $buckets[$key] += (int) $sale->total;
                }
            });

        return ['months' => $months, 'values' => array_values($buckets)];
    }

    /**
     * @param  list<string>  $months
     * @return array<string,mixed>
     */
    private function donations(array $months): array
    {
        $buckets = array_fill_keys($months, 0);

        Donation::query()
            ->whereIn('status', ['recorded', 'paid'])
            ->get(['amount', 'created_at'])
            ->each(function (Donation $donation) use (&$buckets) {
                $key = $donation->created_at?->timezone(DisplayTime::zone())->format('Y-m');

                if ($key !== null && array_key_exists($key, $buckets)) {
                    $buckets[$key] += (int) $donation->amount;
                }
            });

        return ['months' => $months, 'values' => array_values($buckets)];
    }

    /** @return list<array<string,mixed>> */
    private function recentStores(): array
    {
        return Store::query()
            ->with('owner:id,name,email')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Store $store) => [
                'id' => $store->id,
                'name' => $store->name,
                'owner' => $store->owner?->name,
                'created_at' => $store->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /** @return list<array<string,mixed>> */
    private function recentDonations(): array
    {
        return Donation::query()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Donation $donation) => [
                'id' => $donation->id,
                'name' => $donation->publicName(),
                'amount' => $donation->amount,
                'channel' => $donation->channel,
                'status' => $donation->status,
                'created_at' => $donation->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * Kunci bulan "Y-m" dari 11 bulan lalu sampai bulan ini.
     *
     * @return list<string>
     */
    private function monthKeys(): array
    {
        $start = DisplayTime::now()->startOfMonth()->subMonths(self::MONTHS - 1);

        return array_map(
            fn (int $offset) => $start->addMonths($offset)->format('Y-m'),
            range(0, self::MONTHS - 1),
        );
    }

    /**
     * @param  Collection<int,mixed>  $timestamps
     * @param  list<string>  $months
     * @return list<int>
     */
    private function countByMonth($timestamps, array $months): array
    {
        $buckets = array_fill_keys($months, 0);

        foreach ($timestamps as $timestamp) {
            if ($timestamp === null) {
                continue;
            }

            $key = CarbonImmutable::parse($timestamp)->timezone(DisplayTime::zone())->format('Y-m');

            if (array_key_exists($key, $buckets)) {
                $buckets[$key]++;
            }
        }

        return array_values($buckets);
    }
}
