<?php

namespace App\Http\Controllers\Web;

use App\Actions\Donation\RecordDonation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DonationRequest;
use App\Models\Donation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman donasi publik. Yang tampil hanya kanal yang benar-benar bisa
 * dipakai: transfer butuh nomor rekening atau QRIS terisi, tautan eksternal
 * butuh URL-nya.
 */
class DonationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Donate/Index', [
            'presets' => config('donation.presets'),
            'limits' => [
                'min' => (int) config('donation.min'),
                'max' => (int) config('donation.max'),
            ],
            'manual' => $this->manualChannel(),
            'external' => $this->externalLinks(),
            'wall' => $this->wall(),
            'supporters' => Donation::whereIn('status', ['recorded', 'paid'])->count(),
            'donor' => $request->user()?->only(['name', 'email']),
        ]);
    }

    public function store(DonationRequest $request, RecordDonation $record): RedirectResponse
    {
        abort_unless($this->manualChannel() !== null, 404);

        $donation = $record->handle($request->validated(), $request->user());

        return redirect()->route('donate.thanks', $donation);
    }

    public function thanks(Donation $donation): Response
    {
        return Inertia::render('Donate/Thanks', [
            'donation' => [
                'order_id' => $donation->order_id,
                'donor_name' => $donation->publicName(),
                'amount' => $donation->amount,
                'channel' => $donation->channel,
                'status' => $donation->status,
                'message' => $donation->message,
            ],
            'manual' => $donation->channel === 'manual' ? $this->manualChannel() : null,
        ]);
    }

    /** @return array<string,mixed>|null null = kanal manual tidak dikonfigurasi */
    private function manualChannel(): ?array
    {
        $manual = config('donation.manual');

        if (blank($manual['account_number']) && blank($manual['qris_url'])) {
            return null;
        }

        return $manual;
    }

    /** @return list<array<string,string>> */
    private function externalLinks(): array
    {
        return array_values(array_filter(
            config('donation.external'),
            fn (array $link) => filled($link['url']),
        ));
    }

    /**
     * Dinding donatur. Donasi anonim tetap tampil (namanya disamarkan) supaya
     * jumlah dukungan terlihat apa adanya; emailnya tidak pernah ikut keluar.
     *
     * @return list<array<string,mixed>>
     */
    private function wall(): array
    {
        return Donation::query()
            ->whereIn('status', ['recorded', 'paid'])
            ->latest()
            ->limit((int) config('donation.wall_limit'))
            ->get()
            ->map(fn (Donation $donation) => [
                'name' => $donation->publicName(),
                'amount' => $donation->amount,
                'message' => $donation->message,
                'at' => $donation->created_at?->toIso8601String(),
            ])
            ->all();
    }
}
