<?php

use App\Actions\Sale\VoidSale;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use Illuminate\Validation\ValidationException;
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

it('stores barcode and barcode_type from the client', function () {
    $this->postJson('/api/v1/sync/push', [
        'changes' => [envelope('products', 'insert', productPayload([
            'barcode' => '8991002101234',
            'barcode_type' => 'EAN13',
        ]))],
    ])->assertOk();

    $this->assertDatabaseHas('products', [
        'id' => 'prod-1',
        'barcode' => '8991002101234',
        'barcode_type' => 'EAN13',
    ]);
});

it('falls back to the column default when an old client omits barcode_type', function () {
    // Klien versi lama belum punya kolom barcode_type — payload-nya sengaja
    // dihapus kolomnya, bukan dikirim null (kolomnya NOT NULL).
    $payload = productPayload();
    unset($payload['barcode_type']);

    $this->postJson('/api/v1/sync/push', [
        'changes' => [envelope('products', 'insert', $payload)],
    ])->assertOk()->assertJsonPath('rejected', []);

    $this->assertDatabaseHas('products', ['id' => 'prod-1', 'barcode_type' => 'EAN13']);
});

it('stores an open bill without creating financial side effects', function () {
    $this->postJson('/api/v1/sync/push', [
        'changes' => [envelope('sales', 'insert', openBillPayload())],
    ])->assertOk()->assertJsonPath('rejected', []);

    $this->assertDatabaseHas('sales', [
        'id' => 'sale-open-1',
        'store_id' => $this->store->id,
        'status' => 'open',
        'open_bill_label' => 'Beras',
        'opened_at' => ms(100),
        'origin_device_id' => 'device-origin-1',
        'sold_at' => null,
        'session_id' => null,
    ]);
    $this->assertDatabaseCount('cashflow_entries', 0);
});

it('rejects voiding an open bill without financial side effects', function () {
    $this->postJson('/api/v1/sync/push', [
        'changes' => [envelope('sales', 'insert', openBillPayload())],
    ])->assertOk();

    $sale = Sale::query()->findOrFail('sale-open-1');

    expect(fn () => app(VoidSale::class)->handle($sale))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseHas('sales', ['id' => 'sale-open-1', 'status' => 'open']);
    $this->assertDatabaseCount('cashflow_entries', 0);
});

it('rejects foreign open bill mutations and allows the origin device', function () {
    $this->postJson('/api/v1/sync/push', [
        'changes' => [
            envelope('sales', 'insert', openBillPayload([
                'id' => 'sale-open-rehold',
                'number' => 'POS-20260904-002',
            ]), 'obx-open-rehold'),
            envelope('sales', 'insert', openBillPayload([
                'id' => 'sale-open-complete',
                'number' => 'POS-20260904-003',
            ]), 'obx-open-complete'),
            envelope('sales', 'insert', openBillPayload([
                'id' => 'sale-open-delete',
                'number' => 'POS-20260904-004',
            ]), 'obx-open-delete'),
        ],
    ], ['X-Device-Id' => 'device-origin-1'])->assertOk();

    $foreign = $this->postJson('/api/v1/sync/push', [
        'changes' => [
            envelope('sales', 'update', openBillPayload([
                'id' => 'sale-open-rehold',
                'number' => 'POS-20260904-002',
                'open_bill_label' => 'Diubah perangkat asing',
                'origin_device_id' => 'device-foreign-1',
                'updated_at' => ms(200),
            ]), 'obx-foreign-rehold'),
            envelope('sales', 'update', openBillPayload([
                'id' => 'sale-open-complete',
                'number' => 'POS-20260904-003',
                'status' => 'completed',
                'paid' => 18000,
                'sold_at' => ms(200),
                'origin_device_id' => 'device-foreign-1',
                'updated_at' => ms(200),
            ]), 'obx-foreign-complete'),
            envelope('sales', 'delete', [
                'id' => 'sale-open-delete',
                'deleted_at' => ms(200),
            ], 'obx-foreign-delete'),
        ],
    ], ['X-Device-Id' => 'device-foreign-1'])->assertOk();

    expect($foreign->json('acked'))->toBe([])
        ->and($foreign->json('rejected'))->toContain(
            ['id' => 'obx-foreign-rehold', 'reason' => 'forbidden'],
            ['id' => 'obx-foreign-complete', 'reason' => 'forbidden'],
            ['id' => 'obx-foreign-delete', 'reason' => 'forbidden'],
        );

    foreach (['sale-open-rehold', 'sale-open-complete', 'sale-open-delete'] as $id) {
        $this->assertDatabaseHas('sales', [
            'id' => $id,
            'status' => 'open',
            'open_bill_label' => 'Beras',
            'origin_device_id' => 'device-origin-1',
            'updated_at' => ms(100),
            'deleted_at' => null,
        ]);
    }

    $origin = $this->postJson('/api/v1/sync/push', [
        'changes' => [
            envelope('sales', 'update', openBillPayload([
                'id' => 'sale-open-rehold',
                'number' => 'POS-20260904-002',
                'open_bill_label' => 'Diubah perangkat asal',
                'origin_device_id' => 'device-foreign-1',
                'updated_at' => ms(300),
            ]), 'obx-origin-rehold'),
            envelope('sales', 'update', openBillPayload([
                'id' => 'sale-open-complete',
                'number' => 'POS-20260904-003',
                'status' => 'completed',
                'paid' => 18000,
                'sold_at' => ms(300),
                'origin_device_id' => 'device-foreign-1',
                'updated_at' => ms(300),
            ]), 'obx-origin-complete'),
            envelope('sales', 'delete', [
                'id' => 'sale-open-delete',
                'deleted_at' => ms(300),
            ], 'obx-origin-delete'),
        ],
    ], ['X-Device-Id' => 'device-origin-1'])->assertOk();

    expect($origin->json('rejected'))->toBe([])
        ->and($origin->json('acked'))->toContain(
            'obx-origin-rehold',
            'obx-origin-complete',
            'obx-origin-delete',
        );

    $this->assertDatabaseHas('sales', [
        'id' => 'sale-open-rehold',
        'status' => 'open',
        'open_bill_label' => 'Diubah perangkat asal',
        'origin_device_id' => 'device-origin-1',
        'updated_at' => ms(300),
        'deleted_at' => null,
    ]);
    $this->assertDatabaseHas('sales', [
        'id' => 'sale-open-complete',
        'status' => 'completed',
        'paid' => 18000,
        'sold_at' => ms(300),
        'origin_device_id' => 'device-origin-1',
        'updated_at' => ms(300),
        'deleted_at' => null,
    ]);
    $this->assertDatabaseHas('sales', [
        'id' => 'sale-open-delete',
        'status' => 'open',
        'origin_device_id' => 'device-origin-1',
        'updated_at' => ms(300),
        'deleted_at' => ms(300),
    ]);
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
