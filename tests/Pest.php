<?php

use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/** Epoch ms deterministik untuk test (hindari Date agar stabil). */
function ms(int $offset = 0): int
{
    return 1_722_000_000_000 + $offset;
}

/** Seed role/permission global sekali per test. */
function seedRoles(): void
{
    (new RolePermissionSeeder)->run();
}

/** Buat toko + owner. */
function makeStore(?string $name = null): Store
{
    $owner = User::factory()->create();

    return Store::factory()->create([
        'owner_id' => $owner->id,
        'name' => $name ?? 'Toko Uji',
    ]);
}

/**
 * Buat user anggota toko dengan role tertentu (owner|cashier) dan kembalikan
 * user tsb. Role spatie di-assign ber-scope team=store.
 */
function makeMember(Store $store, string $role = 'owner'): User
{
    seedRoles();
    $user = User::factory()->create(['current_store_id' => $store->id]);
    $user->stores()->attach($store->id, ['role' => $role]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($store->id);
    $user->assignRole($role);

    return $user;
}

/** Amplop ChangeEnvelope siap-push. */
function envelope(string $entity, string $op, array $payload, ?string $id = null): array
{
    return [
        'id' => $id ?? 'obx-'.$entity.'-'.($payload['id'] ?? 'x'),
        'entity' => $entity,
        'entityId' => $payload['id'] ?? null,
        'op' => $op,
        'payload' => $payload,
        'createdAt' => ms(),
    ];
}

/** Payload produk lengkap (semua kolom sync). */
function productPayload(array $overrides = []): array
{
    return array_merge([
        'id' => 'prod-1',
        'category_id' => null,
        'name' => 'Kopi Susu',
        'sku' => null,
        'barcode' => null,
        'barcode_type' => 'EAN13',
        'price' => 18000,
        'cost' => 9000,
        'track_stock' => 0,
        'stock' => 0,
        'image_path' => null,
        'active' => 1,
        'created_at' => ms(),
        'updated_at' => ms(),
        'deleted_at' => null,
        'dirty' => 1,
        'sync_version' => 0,
        'remote_id' => null,
    ], $overrides);
}
