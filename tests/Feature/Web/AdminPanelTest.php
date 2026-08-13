<?php

use App\Models\Donation;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->store = makeStore('Toko A');
    $this->owner = makeMember($this->store, 'owner');
});

it('keeps ordinary users out of the platform area', function () {
    $this->actingAs($this->owner)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($this->owner)->get(route('admin.stores.index'))->assertForbidden();
    $this->actingAs($this->owner)->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($this->owner)->get(route('admin.donations.index'))->assertForbidden();
});

// Terpisah: actingAs() bertahan seumur test, jadi cek tamu tidak bisa menumpang
// test di atas — user-nya masih login dan hasilnya 403, bukan redirect.
it('sends a guest to the login page', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

it('shows platform-wide numbers to a superadmin', function () {
    webProduct($this->store, $this->owner);
    $superadmin = makeSuperadmin();

    $this->actingAs($superadmin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Dashboard')
            ->where('kpi.products', 1)
            ->where('kpi.stores', 1)
            ->has('growth.months', 12)
            ->has('revenue.values', 12));
});

it('lists stores across tenants without an active store context', function () {
    $storeB = makeStore('Toko B');
    $superadmin = makeSuperadmin();

    $this->actingAs($superadmin)
        ->get(route('admin.stores.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Stores/Index')
            ->has('stores.data', 2));

    $this->actingAs($superadmin)
        ->get(route('admin.stores.show', $storeB))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Stores/Show')
            ->where('store.name', 'Toko B'));
});

it('promotes and demotes a superadmin', function () {
    $superadmin = makeSuperadmin();
    $target = User::factory()->create();

    $this->actingAs($superadmin)
        ->post(route('admin.users.superadmin', $target))
        ->assertRedirect();

    expect($target->fresh()->isSuperadmin())->toBeTrue();

    $this->actingAs($superadmin)
        ->post(route('admin.users.superadmin', $target))
        ->assertRedirect();

    expect($target->fresh()->isSuperadmin())->toBeFalse();
});

it('refuses to demote yourself', function () {
    $superadmin = makeSuperadmin();

    $this->actingAs($superadmin)
        ->post(route('admin.users.superadmin', $superadmin))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($superadmin->fresh()->isSuperadmin())->toBeTrue();
});

it('filters donations and keeps totals in step with the table', function () {
    $superadmin = makeSuperadmin();

    Donation::create([
        'order_id' => 'DON-1', 'donor_name' => 'Ani', 'amount' => 50000,
        'channel' => 'transfer', 'status' => 'approved',
    ]);
    Donation::create([
        'order_id' => 'DON-2', 'donor_name' => 'Budi', 'amount' => 25000,
        'channel' => 'saweria', 'status' => 'pending',
    ]);

    $this->actingAs($superadmin)
        ->get(route('admin.donations.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Donations/Index')
            ->has('donations.data', 2)
            ->where('totals.amount', 50000)   // yang belum ditinjau belum ikut terkumpul
            ->where('totals.pending', 1));

    $this->actingAs($superadmin)
        ->get(route('admin.donations.index', ['channel' => 'transfer']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('donations.data', 1)
            ->where('totals.count', 1));
});

it('lets a superadmin approve a donation and records who did it', function () {
    $superadmin = makeSuperadmin();
    $donation = Donation::create([
        'order_id' => 'DON-3', 'donor_name' => 'Ani', 'amount' => 50000,
        'channel' => 'transfer', 'status' => 'pending',
    ]);

    $this->actingAs($superadmin)
        ->put(route('admin.donations.update', $donation), ['status' => 'approved'])
        ->assertRedirect();

    $donation->refresh();

    expect($donation->status)->toBe('approved')
        ->and($donation->reviewed_at)->not->toBeNull()
        ->and($donation->reviewed_by)->toBe($superadmin->id);
});

it('keeps a rejected donation off the public wall', function () {
    $superadmin = makeSuperadmin();
    $donation = Donation::create([
        'order_id' => 'DON-5', 'donor_name' => 'Spam', 'amount' => 50000,
        'channel' => 'transfer', 'status' => 'pending', 'message' => 'beli followers murah',
    ]);

    $this->actingAs($superadmin)
        ->put(route('admin.donations.update', $donation), ['status' => 'rejected'])
        ->assertRedirect();

    $this->get(route('donate.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('wall', 0)->where('supporters', 0));
});

it('exports the filtered donation list as csv', function () {
    $superadmin = makeSuperadmin();
    Donation::create([
        'order_id' => 'DON-4', 'donor_name' => 'Ani', 'amount' => 50000,
        'channel' => 'transfer', 'status' => 'approved',
    ]);

    $response = $this->actingAs($superadmin)
        ->get(route('admin.donations.export'))
        ->assertOk();

    $csv = $response->streamedContent();

    expect($csv)->toContain('DON-4')->toContain('Ani');
});

/*
 * Log sync dibaca langsung dari origin_device + updated_at pada row entity,
 * bukan dari tabel audit terpisah — jadi yang diuji di sini adalah bahwa
 * tulisan dari web benar-benar meninggalkan jejak yang bisa dibaca.
 */
it('shows which device wrote last in the sync log', function () {
    webProduct($this->store, $this->owner);
    $superadmin = makeSuperadmin();

    $this->actingAs($superadmin)
        ->get(route('admin.sync.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Sync')
            ->where('totals.web_devices', 1)
            ->where('devices.0.device', 'web:'.$this->owner->id)
            ->where('devices.0.store', 'Toko A')
            ->where('devices.0.is_web', true)
            ->where('devices.0.label', $this->owner->name.' (web)'));
});
