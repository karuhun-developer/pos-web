<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->store = makeStore();
    $this->owner = makeMember($this->store, 'owner');
});

/** Berkas CSV palsu dengan header yang dikenali importer. */
function csvUpload(string $body, string $name = 'produk.csv'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $body);
}

it('downloads a product export as csv', function () {
    webProduct($this->store, $this->owner, ['name' => 'Kopi Susu', 'sku' => 'KOPI-01']);

    $response = $this->actingAs($this->owner)
        ->get(route('io.export', ['dataset' => 'produk', 'format' => 'csv']))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($response->streamedContent())->toContain('KOPI-01')->toContain('Kopi Susu');
});

it('downloads an empty template for a dataset', function () {
    $response = $this->actingAs($this->owner)
        ->get(route('io.template', ['dataset' => 'produk', 'format' => 'csv']))
        ->assertOk();

    // Template membawa header + satu baris contoh, bukan data toko.
    expect($response->streamedContent())->toContain('sku')->toContain('Kopi Susu');
});

it('keeps a cashier away from exports and imports', function () {
    $cashier = makeMember($this->store, 'cashier');

    $this->actingAs($cashier)
        ->get(route('io.export', ['dataset' => 'produk']))
        ->assertForbidden();

    $this->actingAs($cashier)
        ->post(route('io.preview'), [
            'dataset' => 'produk',
            'berkas' => csvUpload("sku,nama,harga\nKOPI-01,Kopi Susu,18000\n"),
        ])
        ->assertForbidden();
});

it('previews an import without writing anything yet', function () {
    Storage::fake('local');

    $existing = webProduct($this->store, $this->owner, ['name' => 'Kopi Susu', 'sku' => 'KOPI-01']);

    $csv = <<<'CSV'
    sku,nama,harga,modal
    KOPI-01,Kopi Susu Gula Aren,20000,10000
    TEH-01,Teh Tawar,8000,3000
    ,Tanpa Penanda,5000,
    CSV;

    $preview = $this->actingAs($this->owner)
        ->post(route('io.preview'), ['dataset' => 'produk', 'berkas' => csvUpload($csv)])
        ->assertRedirect()
        ->assertSessionHas('import_preview')
        ->getSession()
        ->get('import_preview');

    expect($preview['summary'])->toMatchArray(['new' => 1, 'update' => 1, 'error' => 1])
        ->and($preview['rows'][2]['reason'])->toContain('SKU atau barcode');

    // Yang penting dari langkah pratinjau: belum ada yang berubah.
    expect($existing->fresh()->name)->toBe('Kopi Susu')
        ->and(Product::withoutGlobalScopes()->whereNull('deleted_at')->count())->toBe(1);
});

it('commits the previewed rows and skips the broken ones', function () {
    Storage::fake('local');

    $existing = webProduct($this->store, $this->owner, ['name' => 'Kopi Susu', 'sku' => 'KOPI-01']);

    $csv = <<<'CSV'
    sku,nama,harga,modal
    KOPI-01,Kopi Susu Gula Aren,20000,10000
    TEH-01,Teh Tawar,8000,3000
    ,Tanpa Penanda,5000,
    CSV;

    $token = $this->actingAs($this->owner)
        ->post(route('io.preview'), ['dataset' => 'produk', 'berkas' => csvUpload($csv)])
        ->getSession()->get('import_preview')['token'];

    $this->actingAs($this->owner)
        ->post(route('io.commit'), ['dataset' => 'produk', 'token' => $token])
        ->assertRedirect(route('io.index'))
        ->assertSessionHas('success', '2 baris diterapkan. 1 baris dilewati karena error.');

    $existing->refresh();
    $created = Product::withoutGlobalScopes()->where('sku', 'TEH-01')->sole();

    expect($existing->name)->toBe('Kopi Susu Gula Aren')
        ->and($existing->price)->toBe(20000)
        ->and($created->name)->toBe('Teh Tawar')
        ->and($created->store_id)->toBe($this->store->id);

    // Uji regresi yang sama pentingnya dengan CRUD web: hasil impor ditulis
    // lewat WriteEntity, jadi Android ikut menariknya.
    Sanctum::actingAs($this->owner);
    $changes = $this->getJson('/api/v1/sync/pull?entity=products&since=0')->json('changes');

    expect($changes)->toHaveCount(2)
        ->and(collect($changes)->pluck('name')->all())
        ->toContain('Kopi Susu Gula Aren', 'Teh Tawar');
});

it('refuses a row whose category does not exist yet', function () {
    Storage::fake('local');

    $csv = "sku,nama,harga,kategori\nKOPI-01,Kopi Susu,18000,Minumann\n";

    $preview = $this->actingAs($this->owner)
        ->post(route('io.preview'), ['dataset' => 'produk', 'berkas' => csvUpload($csv)])
        ->getSession()->get('import_preview');

    // Salah ketik nama kategori TIDAK boleh diam-diam membuat kategori baru.
    expect($preview['summary']['error'])->toBe(1)
        ->and($preview['rows'][0]['reason'])->toContain('belum ada')
        ->and(Category::withoutGlobalScopes()->count())->toBe(0);
});

it('refuses a commit token that belongs to another session', function () {
    Storage::fake('local');

    $token = $this->actingAs($this->owner)
        ->post(route('io.preview'), [
            'dataset' => 'produk',
            'berkas' => csvUpload("sku,nama,harga\nTEH-01,Teh Tawar,8000\n"),
        ])
        ->getSession()->get('import_preview')['token'];

    // Sesi baru (browser lain): tokennya sah bentuknya dan berkasnya masih di
    // disk, tapi pengikatan token→sesi hilang, jadi commit-nya ditolak. Tanpa
    // ikatan itu, token yang bocor bisa dipakai menulis ke toko orang lain.
    $intruder = makeMember(makeStore('Toko B'), 'owner');
    $this->flushSession();

    $this->actingAs($intruder)
        ->post(route('io.commit'), ['dataset' => 'produk', 'token' => $token])
        ->assertSessionHasErrors('token');

    expect(Product::withoutGlobalScopes()->count())->toBe(0);
});

it('rejects a file that is not a spreadsheet', function () {
    $this->actingAs($this->owner)
        ->post(route('io.preview'), [
            'dataset' => 'produk',
            'berkas' => UploadedFile::fake()->create('gambar.png', 10, 'image/png'),
        ])
        ->assertSessionHasErrors('berkas');
});
