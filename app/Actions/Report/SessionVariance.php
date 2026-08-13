<?php

namespace App\Actions\Report;

use App\Models\CashierSession;
use App\Support\DisplayTime;
use App\Support\ReportPeriod;

/**
 * Selisih laci per sesi kasir: uang yang dihitung dikurangi yang diharapkan.
 * Nilainya berpolaritas (lebih / kurang), jadi digambar menyebar dari nol.
 */
class SessionVariance
{
    /** @return array<string,mixed> */
    public function handle(ReportPeriod $period): array
    {
        $sessions = CashierSession::query()
            ->whereNull('deleted_at')
            ->where('status', 'closed')
            ->whereBetween('opened_at', [$period->startMs(), $period->endMs()])
            ->orderBy('opened_at')
            ->get(['id', 'opened_at', 'closed_at', 'opened_by', 'expected_cash', 'counted_cash', 'difference']);

        $rows = $sessions->map(fn (CashierSession $session) => [
            'id' => $session->id,
            'label' => DisplayTime::toLocal((int) $session->opened_at)->format('d/m H:i'),
            'cashier' => $session->opened_by,
            'expected' => (int) $session->expected_cash,
            'counted' => (int) ($session->counted_cash ?? 0),
            'difference' => (int) ($session->difference ?? 0),
        ]);

        return [
            'rows' => $rows->all(),
            'balanced' => $rows->where('difference', 0)->count(),
            'short' => $rows->filter(fn (array $row) => $row['difference'] < 0)->count(),
            'over' => $rows->filter(fn (array $row) => $row['difference'] > 0)->count(),
            'worst' => (int) $rows->min('difference'),
        ];
    }
}
