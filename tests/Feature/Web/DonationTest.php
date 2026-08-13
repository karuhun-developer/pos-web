<?php

use App\Models\Donation;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;

/**
 * Halaman donasi publik + webhook Paywuz. Kanal manual sengaja tanpa
 * verifikasi (lihat config/donation.php), jadi yang diuji di sini adalah
 * bentuk datanya, bukan keabsahan transfernya.
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
    config()->set('services.paywuz.key', null);
    config()->set('donation.external', [
        ['label' => 'Saweria', 'url' => 'https://saweria.co/uji'],
        ['label' => 'Trakteer', 'url' => null],
    ]);

    $this->get(route('donate.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Donate/Index')
            ->where('paywuz_enabled', false)
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

it('sends the donor to paywuz and keeps the pending row', function () {
    config()->set('services.paywuz.key', 'kunci-uji');
    config()->set('services.paywuz.base_url', 'https://paywuz.test/api/');

    Http::fake([
        'paywuz.test/*' => Http::response([
            'data' => ['id' => 'trx-1', 'paymentUrl' => 'https://paywuz.test/bayar/trx-1'],
        ]),
    ]);

    $this->post(route('donate.store'), [
        'donor_name' => 'Budi',
        'amount' => 100000,
        'channel' => 'paywuz',
        // Inertia::location: redirect biasa untuk kunjungan non-Inertia,
        // header X-Inertia-Location kalau datang dari SPA.
    ])->assertRedirect('https://paywuz.test/bayar/trx-1');

    $donation = Donation::sole();

    expect($donation->status)->toBe('pending')
        ->and($donation->reference)->toBe('trx-1')
        ->and($donation->redirect_url)->toBe('https://paywuz.test/bayar/trx-1');
});

it('cancels the donation when paywuz refuses the checkout', function () {
    config()->set('services.paywuz.key', 'kunci-uji');
    config()->set('services.paywuz.base_url', 'https://paywuz.test/api/');

    Http::fake(['paywuz.test/*' => Http::response(['message' => 'nope'], 500)]);

    $this->post(route('donate.store'), [
        'donor_name' => 'Budi',
        'amount' => 100000,
        'channel' => 'paywuz',
    ])->assertRedirect()->assertSessionHas('error');

    // Barisnya tidak boleh menggantung sebagai "pending" selamanya.
    expect(Donation::sole()->status)->toBe('cancelled');
});

it('rejects a webhook with a bad signature', function () {
    config()->set('services.paywuz.webhook_secret', 'rahasia');

    $this->postJson('/api/v1/webhooks/paywuz', ['orderId' => 'DON-1'], [
        'x-paywuz-signature' => 'sha256=palsu',
        'x-paywuz-event' => 'transaction.paid',
    ])->assertStatus(401);
});

it('marks a donation paid once, no matter how often the webhook arrives', function () {
    config()->set('services.paywuz.webhook_secret', 'rahasia');

    $donation = Donation::create([
        'order_id' => 'DON-9', 'donor_name' => 'Budi', 'amount' => 100000,
        'channel' => 'paywuz', 'status' => 'pending',
    ]);

    $payload = ['data' => ['orderId' => 'DON-9', 'paymentMethod' => 'QRIS']];

    $post = fn () => $this->call(
        'POST',
        '/api/v1/webhooks/paywuz',
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_PAYWUZ_SIGNATURE' => 'sha256='.hash_hmac('sha256', json_encode($payload), 'rahasia'),
            'HTTP_X_PAYWUZ_EVENT' => 'transaction.paid',
        ],
        content: json_encode($payload),
    );

    $post()->assertOk();
    $donation->refresh();
    $paidAt = $donation->paid_at;

    expect($donation->status)->toBe('paid')
        ->and($donation->payment_method)->toBe('QRIS')
        ->and($paidAt)->not->toBeNull();

    // Kiriman ulang tidak boleh menggeser waktu bayar atau menggandakan apa pun.
    $post()->assertOk();

    expect(Donation::count())->toBe(1)
        ->and($donation->fresh()->paid_at->equalTo($paidAt))->toBeTrue();
});

it('never cancels a donation that was already paid', function () {
    config()->set('services.paywuz.webhook_secret', 'rahasia');

    Donation::create([
        'order_id' => 'DON-10', 'donor_name' => 'Budi', 'amount' => 100000,
        'channel' => 'paywuz', 'status' => 'paid', 'paid_at' => now(),
    ]);

    $payload = ['data' => ['orderId' => 'DON-10']];

    $this->call(
        'POST',
        '/api/v1/webhooks/paywuz',
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_PAYWUZ_SIGNATURE' => 'sha256='.hash_hmac('sha256', json_encode($payload), 'rahasia'),
            'HTTP_X_PAYWUZ_EVENT' => 'transaction.expired',
        ],
        content: json_encode($payload),
    )->assertOk();

    expect(Donation::sole()->status)->toBe('paid');
});
