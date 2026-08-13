<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Cache::forget('platform.android_release');
});

/**
 * Nomor versi di landing datang dari rilis GitHub, bukan dari konstanta di kode
 * — angka yang ditulis tangan pasti ketinggalan sekali dua kali rilis.
 */
it('shows the latest android release from github', function () {
    Http::fake([
        'api.github.com/*' => Http::response([
            'tag_name' => 'v0.3.1',
            'html_url' => 'https://github.com/karuhun-developer/pos-android/releases/tag/v0.3.1',
            'published_at' => '2026-08-13T16:21:04Z',
            'assets' => [[
                'name' => 'pos-kacaw-0.3.1.apk',
                'size' => 33487211,
                'browser_download_url' => 'https://github.com/karuhun-developer/pos-android/releases/download/v0.3.1/pos-kacaw-0.3.1.apk',
            ]],
        ]),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Landing')
            ->where('release.version', 'v0.3.1')
            ->where('release.apk', 'https://github.com/karuhun-developer/pos-android/releases/download/v0.3.1/pos-kacaw-0.3.1.apk')
            ->where('release.size', 33487211)
            ->where('repos.android', 'https://github.com/karuhun-developer/pos-android')
            ->where('repos.web', 'https://github.com/karuhun-developer/pos-web'));
});

/** GitHub mati bukan alasan landing ikut mati — tombol unduhnya jatuh ke halaman rilis. */
it('falls back to the releases page when github is unreachable', function () {
    Http::fake(['api.github.com/*' => Http::response(status: 503)]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('release.version', null)
            ->where('release.apk', null)
            ->where('release.url', 'https://github.com/karuhun-developer/pos-android/releases/latest'));
});

/** Sekali tanya, sisanya dari cache — landing tidak boleh menembak GitHub tiap dibuka. */
it('caches the release lookup', function () {
    Http::fake([
        'api.github.com/*' => Http::response(['tag_name' => 'v0.3.1', 'assets' => []]),
    ]);

    $this->get(route('home'))->assertOk();
    $this->get(route('home'))->assertOk();

    // Dihitung yang ke GitHub saja — Inertia juga menembak SSR-nya sendiri.
    $toGithub = collect(Http::recorded())
        ->filter(fn (array $pair) => str_contains($pair[0]->url(), 'api.github.com'));

    expect($toGithub)->toHaveCount(1);
});
