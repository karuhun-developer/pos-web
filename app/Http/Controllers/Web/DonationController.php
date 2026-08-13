<?php

namespace App\Http\Controllers\Web;

use App\Actions\Donation\RecordDonation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DonationRequest;
use App\Models\Donation;
use App\Support\DonationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman donasi publik. Isinya cuma dua hal: ke mana uangnya dikirim, dan
 * formulir singkat untuk mencatatnya. Yang tampil hanya kanal yang benar-benar
 * dikonfigurasi superadmin di /admin/donasi/pengaturan.
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
            'pay' => DonationSettings::forDisplay(),
            'channels' => DonationSettings::channels(),
            'wall' => $this->wall(),
            'supporters' => Donation::query()->approved()->count(),
            'donor' => $request->user()?->only(['name', 'email']),
        ]);
    }

    public function store(DonationRequest $request, RecordDonation $record): RedirectResponse
    {
        abort_unless(DonationSettings::isOpen(), 404);

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
            'pay' => DonationSettings::forDisplay(),
        ]);
    }

    /**
     * Dinding donatur — hanya yang sudah diterima superadmin. Donasi anonim
     * tetap tampil (namanya disamarkan) supaya jumlah dukungan terlihat apa
     * adanya; emailnya tidak pernah ikut keluar.
     *
     * @return list<array<string,mixed>>
     */
    private function wall(): array
    {
        return Donation::query()
            ->approved()
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
