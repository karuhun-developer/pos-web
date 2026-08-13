<?php

use Inertia\Testing\AssertableInertia;

/**
 * Halaman /tentang sengaja terbuka tanpa login: orang yang menimbang mau
 * memakai POS Pro harus bisa melihat versi dan kodenya sebelum bikin akun.
 */
it('shows the version and repository links to a guest', function () {
    config(['about.version' => '1.2.3']);

    $this->get(route('about'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('About')
            ->where('app.version', '1.2.3')
            ->where('app.repository', 'https://github.com/karuhun-developer/pos-web')
            ->where('app.android_repository', 'https://github.com/karuhun-developer/pos-android'));
});
