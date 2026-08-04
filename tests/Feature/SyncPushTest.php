<?php

use App\Models\Product;
use App\Models\Store;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->store = makeStore();
    $this->owner = makeMember($this->store, 'owner');
    Sanctum::actingAs($this->owner);
});

it('applies an insert and scopes it to the active store', function () {
    $res = $this->postJson('/api/v1/sync/push', [
        'changes' => [envelope('products', 'insert', productPayload())],
    ])->assertOk();

    expect($res->json('acked'))->toContain('obx-products-prod-1')
        ->and($res->json('rejected'))->toBe([]);

    $this->assertDatabaseHas('products', [
        'id' => 'prod-1',
        'store_id' => $this->store->id,
        'name' => 'Kopi Susu',
        'price' => 18000,
        'sync_version' => 0,
    ]);
});

it('rejects a stale update but applies a newer one (LWW)', function () {
    $this->postJson('/api/v1/sync/push', [
        'changes' => [envelope('products', 'insert', productPayload(['updated_at' => ms(100)]))],
    ])->assertOk();

    // Lebih lama → stale
    $stale = $this->postJson('/api/v1/sync/push', [
        'changes' => [envelope('products', 'update', productPayload(['name' => 'Lama', 'updated_at' => ms(50)]), 'obx-stale')],
    ])->assertOk();
    expect($stale->json('rejected'))->toContain(['id' => 'obx-stale', 'reason' => 'stale']);
    $this->assertDatabaseHas('products', ['id' => 'prod-1', 'name' => 'Kopi Susu']);

    // Lebih baru → menang
    $this->postJson('/api/v1/sync/push', [
        'changes' => [envelope('products', 'update', productPayload(['name' => 'Baru', 'updated_at' => ms(200)]), 'obx-new')],
    ])->assertOk();
    $this->assertDatabaseHas('products', ['id' => 'prod-1', 'name' => 'Baru', 'sync_version' => 1]);
});

it('turns a delete into a tombstone', function () {
    $this->postJson('/api/v1/sync/push', [
        'changes' => [envelope('products', 'insert', productPayload(['updated_at' => ms(100)]))],
    ])->assertOk();

    $this->postJson('/api/v1/sync/push', [
        'changes' => [envelope('products', 'delete', ['id' => 'prod-1', 'deleted_at' => ms(300)], 'obx-del')],
    ])->assertOk()->assertJsonPath('acked.0', 'obx-del');

    $row = Product::withoutGlobalScopes()->find('prod-1');
    expect($row->deleted_at)->toBe(ms(300));
});

it('rejects unknown and forbidden entities', function () {
    $res = $this->postJson('/api/v1/sync/push', [
        'changes' => [
            envelope('widgets', 'insert', ['id' => 'w1', 'updated_at' => ms()], 'obx-unknown'),
            envelope('settings', 'insert', ['id' => 's1', 'updated_at' => ms()], 'obx-forbidden'),
        ],
    ])->assertOk();

    expect($res->json('rejected'))
        ->toContain(['id' => 'obx-unknown', 'reason' => 'unknown_entity'])
        ->toContain(['id' => 'obx-forbidden', 'reason' => 'forbidden_entity']);
});

it('isolates tenants: store B cannot see store A data', function () {
    // store A (aktif) menerima produk
    $this->postJson('/api/v1/sync/push', [
        'changes' => [envelope('products', 'insert', productPayload())],
    ])->assertOk();

    // store B lain
    $storeB = Store::factory()->create();
    $ownerB = makeMember($storeB, 'owner');
    Sanctum::actingAs($ownerB);

    $this->getJson('/api/v1/sync/pull?entity=products&since=0')
        ->assertOk()
        ->assertJsonPath('changes', []);
});
