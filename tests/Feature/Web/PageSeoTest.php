<?php

use Illuminate\Support\Facades\Http;

/*
 * Panel ini Inertia tanpa SSR, jadi meta yang ditulis Vue tidak pernah sampai
 * ke perayap. Yang diuji di sini HTML mentahnya — kalau judul/deskripsi balik
 * lagi ke sisi Vue, test ini yang jatuh duluan.
 */

beforeEach(function () {
    Http::fake(['api.github.com/*' => Http::response(status: 503)]);
});

it('renders the landing title and description server-side', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('<title inertia>Aplikasi kasir Android yang jalan tanpa internet · POS Kacaw</title>')
        ->toContain('name="description" content="POS Kacaw: aplikasi kasir (POS) Android gratis')
        ->toContain('<meta property="og:title"')
        ->toContain('rel="canonical" href="'.url('/').'"')
        ->not->toContain('name="robots"');
});

it('keeps the head-key of the description in step with GuestLayout.vue', function () {
    // Nilai atribut `inertia` = head-key di sisi Vue. Kalau keduanya beda,
    // head manager Inertia menambah tag deskripsi kedua alih-alih menggantinya.
    expect($this->get('/')->getContent())->toContain('<meta inertia="description" name="description"');

    expect(file_get_contents(resource_path('js/Layouts/GuestLayout.vue')))
        ->toContain('head-key="description"');
});

it('marks pages behind the login as noindex', function () {
    $html = $this->get(route('login'))->assertOk()->getContent();

    expect($html)
        ->toContain('<title inertia>Masuk · POS Kacaw</title>')
        ->toContain('<meta name="robots" content="noindex, nofollow">');
});

it('lets crawlers index the support page', function () {
    $html = $this->get(route('donate.index'))->assertOk()->getContent();

    expect($html)
        ->toContain('<title inertia>Dukung · POS Kacaw</title>')
        ->not->toContain('name="robots"');
});
