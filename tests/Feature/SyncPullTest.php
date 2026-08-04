<?php

use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->store = makeStore();
    $this->owner = makeMember($this->store, 'owner');
    Sanctum::actingAs($this->owner);
});

it('pulls rows newer than the cursor and advances it', function () {
    $this->postJson('/api/v1/sync/push', [
        'changes' => [
            envelope('products', 'insert', productPayload(['id' => 'p1', 'updated_at' => ms(100)]), 'o1'),
            envelope('products', 'insert', productPayload(['id' => 'p2', 'updated_at' => ms(200)]), 'o2'),
        ],
    ])->assertOk();

    $res = $this->getJson('/api/v1/sync/pull?entity=products&since=0')->assertOk();
    expect($res->json('entity'))->toBe('products')
        ->and($res->json('changes'))->toHaveCount(2)
        ->and($res->json('cursor'))->toBe(ms(200));

    // since = cursor → hanya yang lebih baru (kosong)
    $this->getJson('/api/v1/sync/pull?entity=products&since='.ms(200))
        ->assertOk()
        ->assertJsonPath('changes', [])
        ->assertJsonPath('cursor', ms(200));
});

it('includes tombstones in pull', function () {
    $this->postJson('/api/v1/sync/push', [
        'changes' => [envelope('products', 'insert', productPayload(['updated_at' => ms(100)]))],
    ])->assertOk();
    $this->postJson('/api/v1/sync/push', [
        'changes' => [envelope('products', 'delete', ['id' => 'prod-1', 'deleted_at' => ms(300)], 'obx-del')],
    ])->assertOk();

    $res = $this->getJson('/api/v1/sync/pull?entity=products&since='.ms(150))->assertOk();
    expect($res->json('changes.0.id'))->toBe('prod-1')
        ->and($res->json('changes.0.deleted_at'))->toBe(ms(300));
});

it('does not leak server-only columns in pull payloads', function () {
    $this->postJson('/api/v1/sync/push', [
        'changes' => [envelope('products', 'insert', productPayload())],
    ])->assertOk();

    $row = $this->getJson('/api/v1/sync/pull?entity=products&since=0')->json('changes.0');
    expect($row)->not->toHaveKey('store_id')
        ->and($row)->not->toHaveKey('origin_device')
        ->and($row)->toHaveKey('dirty')
        ->and($row['dirty'])->toBe(0);
});

it('validates the entity query parameter', function () {
    $this->getJson('/api/v1/sync/pull?entity=widgets&since=0')
        ->assertStatus(422)
        ->assertJsonValidationErrors('entity');
});
