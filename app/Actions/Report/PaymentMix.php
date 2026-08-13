<?php

namespace App\Actions\Report;

use App\Models\Sale;
use App\Support\ReportPeriod;

/**
 * Komposisi metode bayar. Digambar sebagai satu batang bertumpuk, bukan pie:
 * membandingkan panjang segmen jauh lebih mudah daripada membandingkan sudut,
 * dan totalnya tetap terbaca sebagai satu keseluruhan.
 */
class PaymentMix
{
    private const LABELS = [
        'cash' => 'Tunai',
        'qris' => 'QRIS',
        'transfer' => 'Transfer',
        'card' => 'Kartu',
    ];

    /** @return array<string,mixed> */
    public function handle(ReportPeriod $period): array
    {
        $rows = Sale::query()
            ->whereNull('deleted_at')
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$period->startMs(), $period->endMs()])
            ->groupBy('payment_method')
            ->selectRaw('payment_method, count(*) as orders, sum(total) as revenue')
            ->orderByDesc('revenue')
            ->get();

        $total = (int) $rows->sum('revenue');

        return [
            'total' => $total,
            'rows' => $rows->map(fn ($row) => [
                'method' => (string) $row->payment_method,
                'label' => self::LABELS[$row->payment_method] ?? ucfirst((string) $row->payment_method),
                'orders' => (int) $row->orders,
                'revenue' => (int) $row->revenue,
                'share' => $total > 0 ? round(((int) $row->revenue / $total) * 100, 1) : 0.0,
            ])->all(),
        ];
    }
}
