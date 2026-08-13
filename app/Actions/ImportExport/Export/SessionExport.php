<?php

namespace App\Actions\ImportExport\Export;

use App\Actions\ImportExport\Contracts\Exporter;
use App\Models\CashierSession;
use App\Support\DisplayTime;
use App\Support\ReportPeriod;

class SessionExport implements Exporter
{
    public function filename(ReportPeriod $period): string
    {
        return "sesi-kasir-{$period->from->format('Ymd')}-{$period->to->format('Ymd')}";
    }

    public function headers(): array
    {
        return ['dibuka', 'ditutup', 'kasir', 'status', 'modal_awal', 'seharusnya', 'dihitung', 'selisih', 'catatan'];
    }

    public function rows(ReportPeriod $period): iterable
    {
        $sessions = CashierSession::query()
            ->whereNull('deleted_at')
            ->whereBetween('opened_at', [$period->startMs(), $period->endMs()])
            ->orderBy('opened_at');

        foreach ($sessions->lazy() as $session) {
            yield [
                DisplayTime::toLocal((int) $session->opened_at)->format('Y-m-d H:i:s'),
                $session->closed_at ? DisplayTime::toLocal((int) $session->closed_at)->format('Y-m-d H:i:s') : null,
                $session->opened_by,
                $session->status,
                (int) $session->opening_cash,
                (int) $session->expected_cash,
                $session->counted_cash === null ? null : (int) $session->counted_cash,
                $session->difference === null ? null : (int) $session->difference,
                $session->note,
            ];
        }
    }
}
