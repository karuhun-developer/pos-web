<?php

use App\Models\Store;
use Laravel\Sanctum\Sanctum;

it('lets an owner create a new outlet and becomes its owner', function () {
    $store = makeStore();
    $owner = makeMember($store, 'owner');
    Sanctum::actingAs($owner);

    $this->postJson('/api/v1/stores', ['name' => 'Cabang Bandung'])
        ->assertCreated()
        ->assertJsonPath('store.name', 'Cabang Bandung')
        ->assertJsonPath('store.role', 'owner')
        ->assertJsonCount(2, 'stores'); // toko awal + cabang baru

    $this->assertDatabaseHas('stores', ['name' => 'Cabang Bandung', 'owner_id' => $owner->id]);
});

it('lets an owner rename their outlet', function () {
    $store = makeStore('Lama');
    $owner = makeMember($store, 'owner');
    Sanctum::actingAs($owner);

    $this->patchJson("/api/v1/stores/{$store->id}", ['name' => 'Baru'])
        ->assertOk()
        ->assertJsonPath('store.name', 'Baru');

    expect($store->fresh()->name)->toBe('Baru');
});

it('forbids a cashier from renaming their outlet', function () {
    $store = makeStore('Toko');
    $cashier = makeMember($store, 'cashier');
    Sanctum::actingAs($cashier);

    $this->patchJson("/api/v1/stores/{$store->id}", ['name' => 'Hack'])
        ->assertForbidden();

    expect($store->fresh()->name)->toBe('Toko');
});

it('forbids renaming an outlet the user does not belong to', function () {
    $mine = makeStore('Punyaku');
    $owner = makeMember($mine, 'owner');

    $other = Store::factory()->create(['name' => 'Toko Orang']);
    Sanctum::actingAs($owner);

    $this->patchJson("/api/v1/stores/{$other->id}", ['name' => 'Hack'])
        ->assertForbidden();

    expect($other->fresh()->name)->toBe('Toko Orang');
});

it('requires authentication to manage outlets', function () {
    $this->postJson('/api/v1/stores', ['name' => 'X'])->assertUnauthorized();
});
