<?php

use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Penjaga paling murah untuk kelas bug yang cuma kelihatan di browser: halaman
 * yang komponen Vue-nya belum dibuat, atau props yang hilang karena middleware
 * belum sempat berjalan. Semua route GET web disusuri di sini; dengan
 * `inertia.pages.ensure_pages_exist` menyala di luar produksi, komponen yang
 * tidak ada bikin render gagal — bukan lolos test lalu mati di browser.
 */
beforeEach(function () {
    $this->store = makeStore('Toko Asap');
    $this->owner = makeMember($this->store, 'owner');
});

/** Route GET halaman web (bukan API, bukan unduhan berkas) tanpa parameter. */
function pageRoutes(): array
{
    $skip = [
        'io.export', 'io.template',   // unduhan berkas, bukan halaman Inertia
        'google.redirect', 'google.callback', // redirect ke Google, bukan halaman
        'login', 'register', 'donate.thanks',  // diuji terpisah / butuh state
        'sanctum.csrf-cookie',        // endpoint token, balasannya 204 tanpa isi
    ];

    return collect(Route::getRoutes()->getRoutesByMethod()['GET'] ?? [])
        ->filter(fn ($route) => in_array('web', $route->gatherMiddleware(), true))
        ->filter(fn ($route) => $route->getName() !== null)
        ->reject(fn ($route) => in_array($route->getName(), $skip, true))
        ->reject(fn ($route) => str_contains($route->uri(), '{'))
        ->pluck('uri')
        ->unique()
        ->values()
        ->all();
}

it('renders every parameterless web page for the store owner', function () {
    $paths = pageRoutes();

    expect($paths)->not->toBeEmpty();

    foreach ($paths as $path) {
        $response = $this->actingAs($this->owner)->get('/'.ltrim($path, '/'));

        expect($response->status())
            // /admin butuh superadmin; sisanya harus benar-benar terbuka.
            ->toBeIn([200, 403], "GET /{$path} mengembalikan {$response->status()}");
    }
});

it('renders every parameterless web page for a superadmin', function () {
    $superadmin = makeSuperadmin(['current_store_id' => $this->store->id]);
    $superadmin->stores()->attach($this->store->id, ['role' => 'owner']);

    foreach (pageRoutes() as $path) {
        $this->actingAs($superadmin)->get('/'.ltrim($path, '/'))->assertOk();
    }
});

it('renders the detail pages that need a record', function () {
    $product = webProduct($this->store, $this->owner);

    $sale = Sale::withoutGlobalScopes()->forceCreate([
        'id' => 'sale-smoke-1',
        'store_id' => $this->store->id,
        'session_id' => null,
        'number' => 'INV-1',
        'subtotal' => 18000,
        'discount' => 0,
        'tax' => 0,
        'total' => 18000,
        'paid' => 18000,
        'change_due' => 0,
        'payment_method' => 'cash',
        'status' => 'completed',
        'sold_at' => ms(),
        'created_at' => ms(),
        'updated_at' => ms(),
        'deleted_at' => null,
        'sync_version' => 1,
    ]);

    $this->actingAs($this->owner)->get(route('products.edit', $product))->assertOk();
    $this->actingAs($this->owner)->get(route('sales.show', $sale))->assertOk();
});

/*
 * Regresi khusus: Inertia\Middleware memanggil share() SEBELUM $next(), jadi
 * props yang dievaluasi langsung berjalan sebelum middleware `store` menyetel
 * StoreContext. Gejalanya halus dan cuma kelihatan di browser — semua tombol
 * tulis hilang karena auth.user.permissions kosong.
 */
it('shares the active store and its permissions with every page', function () {
    $this->actingAs($this->owner)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.current_store.id', $this->store->id)
            ->where('auth.current_store.name', 'Toko Asap')
            ->where('auth.user.is_superadmin', false)
            ->where('auth.user.permissions', fn ($permissions) => collect($permissions)->contains('catalog.manage'))
        );
});

it('shares no store for a user who has none', function () {
    $stranger = User::factory()->create(['current_store_id' => null]);

    // Tanpa toko aktif, middleware `store` menolak duluan — yang penting halaman
    // tamu tetap bisa dirender tanpa membocorkan toko orang lain.
    Store::factory()->create(['owner_id' => $stranger->id]);

    $this->actingAs($stranger)->get(route('donate.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('auth.current_store', null));
});
