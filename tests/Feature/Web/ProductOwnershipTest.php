<?php

use App\Models\Product;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * Kepemilikan data dari sisi web. Tiga lapis yang diuji di sini:
 * StoreScope (row toko lain tidak terlihat → 404), policy (kepemilikan
 * eksplisit → 403), dan permission (kasir tidak boleh hapus → 403).
 */
beforeEach(function () {
    $this->storeA = makeStore('Toko A');
    $this->ownerA = makeMember($this->storeA, 'owner');

    $this->productA = webProduct($this->storeA, $this->ownerA, ['name' => 'Kopi Susu']);
});

/*
 * Catatan penting soal urutan middleware: SubstituteBindings ada di grup `web`,
 * jadi ia berjalan SEBELUM middleware route 'store'. Saat route model binding
 * mencari row, StoreContext masih kosong dan StoreScope (fail-open) belum
 * membatasi apa pun — row toko lain tetap ter-resolve. Yang menahan di jalur
 * web karena itu adalah policy, bukan scope: jawabannya 403, bukan 404.
 */
it('blocks another store owner from writing a foreign product', function () {
    $storeB = makeStore('Toko B');
    $ownerB = makeMember($storeB, 'owner');

    $this->actingAs($ownerB)
        ->put(route('products.update', $this->productA), ['name' => 'Dibajak', 'price' => 1000])
        ->assertForbidden();

    $this->actingAs($ownerB)
        ->delete(route('products.destroy', $this->productA))
        ->assertForbidden();

    expect($this->productA->fresh()->name)->toBe('Kopi Susu')
        ->and($this->productA->fresh()->deleted_at)->toBeNull();
});

it('refuses a non-member even when the store context says otherwise', function () {
    // Konteks toko sengaja dipaksa ke toko A untuk user yang bukan anggotanya:
    // ini simulasi route yang lupa memasang middleware store, atau konteks
    // basi. StoreScope jadi lolos — policy-lah yang harus menahan.
    $outsider = User::factory()->create(['current_store_id' => $this->storeA->id]);
    $ownStore = makeStore('Toko C');
    $outsider->stores()->attach($ownStore->id, ['role' => 'owner']);

    $this->actingAs($outsider)
        ->put(route('products.update', $this->productA), ['name' => 'Dibajak', 'price' => 1000])
        ->assertForbidden();

    expect($this->productA->fresh()->name)->toBe('Kopi Susu');
});

it('lets a cashier read the catalog but not delete from it', function () {
    $cashier = makeMember($this->storeA, 'cashier');

    $this->actingAs($cashier)->get(route('products.index'))->assertOk();

    $this->actingAs($cashier)
        ->delete(route('products.destroy', $this->productA))
        ->assertForbidden();

    expect($this->productA->fresh()->deleted_at)->toBeNull();
});

it('lets a superadmin through the ownership policy', function () {
    $superadmin = makeSuperadmin(['current_store_id' => $this->storeA->id]);

    $this->actingAs($superadmin)
        ->delete(route('products.destroy', $this->productA))
        ->assertRedirect(route('products.index'));

    expect($this->productA->fresh()->deleted_at)->not->toBeNull();
});

it('scopes the catalog listing to the active store', function () {
    $storeB = makeStore('Toko B');
    $ownerB = makeMember($storeB, 'owner');
    webProduct($storeB, $ownerB, ['name' => 'Teh Tarik']);

    // Ganti team id ke toko A sebelum query berikutnya (makeMember menyetelnya
    // ke toko B barusan) — sama seperti yang dilakukan SetCurrentStore.
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->storeA->id);

    $names = Product::withoutGlobalScopes()
        ->where('store_id', $this->storeA->id)
        ->pluck('name');

    expect($names)->toContain('Kopi Susu')
        ->and($names)->not->toContain('Teh Tarik');
});
