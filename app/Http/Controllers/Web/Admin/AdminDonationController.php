<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Donation\UpdateDonationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DonationStatusRequest;
use App\Models\Donation;
use App\Support\DisplayTime;
use App\Support\Spreadsheet;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Daftar donasi untuk superadmin — sekaligus antrean moderasi: nama & pesan
 * baru tampil di halaman publik setelah diterima di sini.
 *
 * Filter, tabel, grafik, dan ekspor semuanya membaca dari SATU builder
 * ber-filter — jadi angka di grafik selalu bercerita hal yang sama dengan
 * tabel di bawahnya dan dengan berkas yang diunduh.
 */
class AdminDonationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Donation::class);

        $donations = $this->filtered($request)
            ->with('reviewer:id,name')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $donations->getCollection()->transform(fn (Donation $donation) => [
            'id' => $donation->id,
            'order_id' => $donation->order_id,
            'donor_name' => $donation->donor_name,
            'donor_email' => $donation->donor_email,
            'is_anonymous' => $donation->is_anonymous,
            'amount' => $donation->amount,
            'message' => $donation->message,
            'channel' => $donation->channel,
            'status' => $donation->status,
            'created_at' => $donation->created_at?->toIso8601String(),
            'reviewed_at' => $donation->reviewed_at?->toIso8601String(),
            'reviewer' => $donation->reviewer?->name,
        ]);

        return Inertia::render('Admin/Donations/Index', [
            'donations' => $donations,
            'filters' => $this->filters($request),
            'options' => [
                'channels' => Donation::CHANNELS,
                'statuses' => Donation::STATUSES,
            ],
            'totals' => $this->totals($request),
            'monthly' => $this->monthly($request),
        ]);
    }

    public function update(DonationStatusRequest $request, Donation $donation, UpdateDonationStatus $update): RedirectResponse
    {
        $this->authorize('update', $donation);

        $update->handle($donation, $request->validated('status'), $request->user());

        return back()->with('success', 'Status donasi diperbarui.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Donation::class);

        $query = $this->filtered($request);

        return Spreadsheet::download(
            'csv',
            'donasi-'.DisplayTime::now()->format('Ymd'),
            ['kode', 'tanggal', 'nama', 'email', 'anonim', 'jumlah', 'kanal', 'status', 'ditinjau', 'pesan'],
            (function () use ($query) {
                foreach ($query->orderBy('created_at')->cursor() as $donation) {
                    yield [
                        $donation->order_id,
                        $donation->created_at?->timezone(DisplayTime::zone())->format('Y-m-d H:i'),
                        $donation->donor_name,
                        $donation->donor_email,
                        $donation->is_anonymous ? 'ya' : 'tidak',
                        $donation->amount,
                        $donation->channel,
                        $donation->status,
                        $donation->reviewed_at?->timezone(DisplayTime::zone())->format('Y-m-d H:i'),
                        $donation->message,
                    ];
                }
            })(),
        );
    }

    /** @return Builder<Donation> */
    private function filtered(Request $request): Builder
    {
        $filters = $this->filters($request);

        return Donation::query()
            ->when($filters['channel'] !== 'all', fn ($query) => $query->where('channel', $filters['channel']))
            ->when($filters['status'] !== 'all', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['q'] !== '', fn ($query) => $query->where(
                fn ($sub) => $sub->where('donor_name', 'like', "%{$filters['q']}%")
                    ->orWhere('donor_email', 'like', "%{$filters['q']}%")
                    ->orWhere('order_id', 'like', "%{$filters['q']}%"),
            ))
            ->when($filters['from'] !== '', fn ($query) => $query->where(
                'created_at', '>=', DisplayTime::toLocal(DisplayTime::startOfDayMs($filters['from'])),
            ))
            ->when($filters['to'] !== '', fn ($query) => $query->where(
                'created_at', '<=', DisplayTime::toLocal(DisplayTime::endOfDayMs($filters['to'])),
            ));
    }

    /** @return array<string,string> */
    private function filters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'channel' => (string) $request->query('channel', 'all'),
            'status' => (string) $request->query('status', 'all'),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ];
    }

    /** @return array<string,int> */
    private function totals(Request $request): array
    {
        $query = $this->filtered($request);

        return [
            'count' => (clone $query)->count(),
            'amount' => (int) (clone $query)->approved()->sum('amount'),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
        ];
    }

    /**
     * Donasi per bulan (12 bulan terakhir) — bar sequential satu hue di layar.
     * Bucketing di PHP dengan zona tampilan, sama seperti laporan lain.
     *
     * @return array<string,mixed>
     */
    private function monthly(Request $request): array
    {
        $start = DisplayTime::now()->startOfMonth()->subMonths(11);
        $months = array_map(fn (int $i) => $start->addMonths($i)->format('Y-m'), range(0, 11));
        $buckets = array_fill_keys($months, 0);

        $this->filtered($request)
            ->approved()
            ->where('created_at', '>=', $start)
            ->select(['amount', 'created_at'])
            ->cursor()
            ->each(function (Donation $donation) use (&$buckets) {
                $key = $donation->created_at?->timezone(DisplayTime::zone())->format('Y-m');

                if ($key !== null && array_key_exists($key, $buckets)) {
                    $buckets[$key] += (int) $donation->amount;
                }
            });

        return ['months' => $months, 'values' => array_values($buckets)];
    }
}
