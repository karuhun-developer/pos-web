<?php

use Laravel\Sanctum\Sanctum;

it('allows a cashier to push (has sync.push)', function () {
    $store = makeStore();
    $cashier = makeMember($store, 'cashier');
    Sanctum::actingAs($cashier);

    $this->postJson('/api/v1/sync/push', [
        'changes' => [envelope('products', 'insert', productPayload())],
    ])->assertOk();
});

it('denies unauthenticated access to sync endpoints', function () {
    $this->postJson('/api/v1/sync/push', ['changes' => []])->assertUnauthorized();
    $this->getJson('/api/v1/sync/pull?entity=products')->assertUnauthorized();
});

it('scopes cashier role to its own store (no cross-store permission)', function () {
    $storeA = makeStore('A');
    $cashier = makeMember($storeA, 'cashier');

    $storeB = makeStore('B');
    // cashier bukan anggota store B → tak bisa mengaktifkan store B
    Sanctum::actingAs($cashier);

    $this->getJson('/api/v1/sync/pull?entity=products&since=0', ['X-Store-Id' => (string) $storeB->id])
        ->assertForbidden();
});
