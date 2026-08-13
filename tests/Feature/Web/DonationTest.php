<?php

use App\Models\Donation;
use Inertia\Testing\AssertableInertia;

/**
 * Halaman donasi publik. Donasi sengaja tanpa verifikasi apa pun (lihat
 * config/donation.php), jadi yang diuji di sini bentuk datanya — bukan
 * keabsahan transfernya.
 */
beforeEach(function () {
    config()->set('donation.manual', [
        'bank' => 'BCA',
        'account_number' => '1234567890',
        'account_name' => 'Yayasan Uji',
        'qris_url' => null,
    ]);
});

it('shows only the channels that are actually configured', function () {
    config()->set('donation.external', [
        ['label' => 'Saweria', 'url' => 'https://saweria.co/uji'],
        ['label' => 'Trakteer', 'url' => null],
    ]);

    $this->get(route('donate.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Donate/Index')
            ->where('manual.bank', 'BCA')
            // Trakteer tanpa URL tidak ikut tampil.
            ->has('external', 1)
            ->where('external.0.label', 'Saweria'));
});

it('records a manual donation without any verification', function () {
    $this->post(route('donate.store'), [
        'donor_name' => 'Ani',
        'amount' => 50000,
        'channel' => 'manual',
        'message' => 'Semangat!',
    ])->assertRedirect();

    $donation = Donation::sole();

    expect($donation->status)->toBe('recorded')
        ->and($donation->amount)->toBe(50000)
        ->and($donation->paid_at)->toBeNull()
        ->and($donation->order_id)->toStartWith('DON-');

    $this->get(route('donate.thanks', $donation))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Donate/Thanks')
            ->where('donation.donor_name', 'Ani')
            ->where('manual.account_number', '1234567890'));
});

it('hides an anonymous donor name on the wall', function () {
    $this->post(route('donate.store'), [
        'donor_name' => 'Ani',
        'amount' => 25000,
        'channel' => 'manual',
        'is_anonymous' => true,
    ])->assertRedirect();

    $this->get(route('donate.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('wall', 1)
            ->where('wall.0.name', fn (string $name) => $name !== 'Ani')
            ->where('supporters', 1));
});

it('refuses an amount below the minimum', function () {
    $this->post(route('donate.store'), [
        'donor_name' => 'Ani',
        'amount' => 100,
        'channel' => 'manual',
    ])->assertSessionHasErrors('amount');

    expect(Donation::count())->toBe(0);
});
