<?php

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * Uji regresi terpenting dari seluruh UI web: apa pun yang ditulis lewat web
 * HARUS bisa ditarik perangkat Android. Kalau tulisan web tidak lewat
 * WriteEntity, updated_at-nya kosong dan PullChanges (`where updated_at >
 * since`) tidak akan pernah membawanya — datanya "ada" tapi tidak pernah
 * sampai ke kasir.
 */
beforeEach(function () {
    $this->store = makeStore();
    $this->owner = makeMember($this->store, 'owner');
});

it('pulls a product created from the web', function () {
    $product = webProduct($this->store, $this->owner, [
        'name' => 'Kopi Susu',
        'sku' => 'KOPI-01',
        'price' => 18000,
    ]);

    Sanctum::actingAs($this->owner);
    $changes = $this->getJson('/api/v1/sync/pull?entity=products&since=0')
        ->assertOk()
        ->json('changes');

    expect($changes)->toHaveCount(1)
        ->and($changes[0]['id'])->toBe($product->id)
        ->and($changes[0]['name'])->toBe('Kopi Susu')
        ->and($changes[0]['sku'])->toBe('KOPI-01')
        // Epoch ms, bukan detik: 13 digit.
        ->and($changes[0]['updated_at'])->toBeGreaterThan(1_600_000_000_000);
});

it('sends a tombstone when a product is deleted from the web', function () {
    $product = webProduct($this->store, $this->owner);

    $this->actingAs($this->owner)
        ->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'));

    Sanctum::actingAs($this->owner);
    $row = $this->getJson('/api/v1/sync/pull?entity=products&since=0')->json('changes.0');

    expect($row['id'])->toBe($product->id)
        ->and($row['deleted_at'])->not->toBeNull()
        // Baris tetap ada di server (tombstone), bukan dihapus permanen.
        ->and(Product::withoutGlobalScopes()->whereKey($product->id)->exists())->toBeTrue();
});

it('turns an uploaded product image into a media row the device can pull', function () {
    Storage::fake('public');

    $this->actingAs($this->owner)
        ->post(route('products.store'), [
            'name' => 'Kopi Susu',
            'price' => 18000,
            'cost' => 9000,
            'active' => true,
            'image' => UploadedFile::fake()->image('kopi.jpg', 320, 320),
        ])
        ->assertRedirect(route('products.index'));

    $product = Product::withoutGlobalScopes()->where('store_id', $this->store->id)->firstOrFail();

    // Kolom produk menyimpan ref, bukan path file — klien menerjemahkannya
    // lewat tabel media (pos-kacaw/src/stores/media.ts).
    expect($product->image_path)->toStartWith('media://');

    Sanctum::actingAs($this->owner);
    $media = $this->getJson('/api/v1/sync/pull?entity=media&since=0')->assertOk()->json('changes.0');

    expect($media['id'])->toBe(str_replace('media://', '', (string) $product->image_path))
        ->and($media['mime'])->toBe('image/jpeg')
        // Byte-nya tidak ikut turun di pull; klien mengunduh dari remote_url.
        ->and($media['remote_url'])->not->toBeNull()
        ->and($media['bytes'])->toBeGreaterThan(0);
});

it('advances updated_at on every web edit so the change is pulled again', function () {
    $product = webProduct($this->store, $this->owner);
    $before = (int) $product->updated_at;

    $this->actingAs($this->owner)
        ->put(route('products.update', $product), [
            'name' => 'Kopi Susu Gula Aren',
            'price' => 20000,
        ])
        ->assertRedirect(route('products.index'));

    Sanctum::actingAs($this->owner);
    $row = $this->getJson('/api/v1/sync/pull?entity=products&since='.$before)->json('changes.0');

    expect($row['name'])->toBe('Kopi Susu Gula Aren')
        ->and($row['updated_at'])->toBeGreaterThan($before);
});
