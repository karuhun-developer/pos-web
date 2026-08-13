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
        'channel' => 'manual', 'status' => 'recorded',
    ]);
    Donation::create([
        'order_id' => 'DON-2', 'donor_name' => 'Budi', 'amount' => 25000,
        'channel' => 'paywuz', 'status' => 'pending',
    ]);

    $this->actingAs($superadmin)
        ->get(route('admin.donations.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Donations/Index')
            ->has('donations.data', 2)
            ->where('totals.amount', 50000)   // pending belum dihitung terkumpul
            ->where('totals.pending', 1));

    $this->actingAs($superadmin)
        ->get(route('admin.donations.index', ['channel' => 'manual']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('donations.data', 1)
            ->where('totals.count', 1));
});

it('lets a superadmin mark a manual donation as paid', function () {
    $superadmin = makeSuperadmin();
    $donation = Donation::create([
        'order_id' => 'DON-3', 'donor_name' => 'Ani', 'amount' => 50000,
        'channel' => 'manual', 'status' => 'recorded',
    ]);

    $this->actingAs($superadmin)
        ->put(route('admin.donations.update', $donation), ['status' => 'paid'])
        ->assertRedirect();

    $donation->refresh();

    expect($donation->status)->toBe('paid')
        ->and($donation->paid_at)->not->toBeNull();
});

it('exports the filtered donation list as csv', function () {
    $superadmin = makeSuperadmin();
    Donation::create([
        'order_id' => 'DON-4', 'donor_name' => 'Ani', 'amount' => 50000,
        'channel' => 'manual', 'status' => 'recorded',
    ]);

    $response = $this->actingAs($superadmin)
        ->get(route('admin.donations.export'))
        ->assertOk();

    $csv = $response->streamedContent();

    expect($csv)->toContain('DON-4')->toContain('Ani');
});
