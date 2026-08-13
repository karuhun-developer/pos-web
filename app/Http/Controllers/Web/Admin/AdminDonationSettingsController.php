<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Admin\SaveDonationSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DonationSettingsRequest;
use App\Models\Donation;
use App\Support\DonationSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ke mana orang harus mengirim uangnya. Diatur di sini, bukan di .env, karena
 * nomor rekening dan gambar QRIS berganti tanpa alasan teknis apa pun.
 */
class AdminDonationSettingsController extends Controller
{
    public function edit(): Response
    {
        $this->authorize('manage', Donation::class);

        $settings = DonationSettings::all();

        return Inertia::render('Admin/Donations/Settings', [
            'settings' => [
                ...$settings,
                'qris_url' => DonationSettings::qrisUrl(),
            ],
        ]);
    }

    public function update(DonationSettingsRequest $request, SaveDonationSettings $save): RedirectResponse
    {
        $save->handle($request->validated(), $request->file('qris'));

        return back()->with('success', 'Cara berdonasi diperbarui.');
    }
}
