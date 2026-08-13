<?php

use App\Models\Donation;
use App\Support\PlatformSettings;
use Inertia\Testing\AssertableInertia;

/**
 * Halaman donasi publik. Pembayarannya sengaja tanpa verifikasi apa pun —
 * yang dimoderasi adalah nama & pesan yang tampil di halaman ini, jadi itu
 * yang diuji di sini.
 */
beforeEach(function () {
    PlatformSettings::put('donation', [
        'qris_path' => null,
        'banks' => [
            ['bank' => 'BCA', 'account_number' => '1234567890', 'account_name' => 'Yayasan Uji'],
        ],
        'saweria_url' => 'https://saweria.co/uji',
        'note' => null,
    ]);
});

it('shows only the channels that are actually configured', function () {
    $this->get(route('donate.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Donate/Index')
            ->where('pay.banks.0.bank', 'BCA')
            // QRIS belum diunggah, jadi kanalnya tidak ditawarkan.
            ->where('channels', ['transfer', 'saweria']));
});

it('records a donation as pending so it never reaches the public wall unreviewed', function () {
    $this->post(route('donate.store'), [
        'donor_name' => 'Ani',
        'amount' => 50000,
        'channel' => 'transfer',
        'message' => 'Semangat!',
    ])->assertRedirect();

    $donation = Donation::sole();

    expect($donation->status)->toBe('pending')
        ->and($donation->amount)->toBe(50000)
        ->and($donation->reviewed_at)->toBeNull()
        ->and($donation->order_id)->toStartWith('DON-');

    $this->get(route('donate.thanks', $donation))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Donate/Thanks')
            ->where('donation.donor_name', 'Ani')
            ->where('pay.banks.0.account_number', '1234567890'));

    // Belum ditinjau: tidak tampil di dinding dan tidak dihitung sebagai pendukung.
    $this->get(route('donate.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('wall', 0)
            ->where('supporters', 0));
});

it('hides an anonymous donor name on the wall', function () {
    $this->post(route('donate.store'), [
        'donor_name' => 'Ani',
        'amount' => 25000,
        'channel' => 'transfer',
        'is_anonymous' => true,
    ])->assertRedirect();

    Donation::sole()->update(['status' => 'approved']);

    $this->get(route('donate.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('wall', 1)
            ->where('wall.0.name', fn (string $name) => $name !== 'Ani')
            ->where('supporters', 1));
});

it('refuses a channel that is not configured', function () {
    $this->post(route('donate.store'), [
        'donor_name' => 'Ani',
        'amount' => 50000,
        'channel' => 'qris',
    ])->assertSessionHasErrors('channel');

    expect(Donation::count())->toBe(0);
});

it('refuses an amount below the minimum', function () {
    $this->post(route('donate.store'), [
        'donor_name' => 'Ani',
        'amount' => 100,
        'channel' => 'transfer',
    ])->assertSessionHasErrors('amount');

    expect(Donation::count())->toBe(0);
});

it('closes the page entirely when no payment target is configured', function () {
    PlatformSettings::put('donation', ['qris_path' => null, 'banks' => [], 'saweria_url' => null, 'note' => null]);

    $this->get(route('donate.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('channels', []));

    $this->post(route('donate.store'), [
        'donor_name' => 'Ani',
        'amount' => 50000,
        'channel' => 'transfer',
    ])->assertSessionHasErrors('channel');
});
