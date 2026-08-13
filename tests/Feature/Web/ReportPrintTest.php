<?php

/**
 * Versi cetak laporan: HTML biasa (bukan Inertia) supaya browser yang
 * mencetaknya bisa memberi PDF tanpa pustaka PDF tambahan. Yang dijaga di sini
 * dua hal: izinnya sama ketat dengan halaman laporan, dan rentang waktunya ikut
 * dari querystring — kertas yang isinya beda dari layar lebih berbahaya
 * daripada tidak bisa dicetak sama sekali.
 */
beforeEach(function () {
    $this->store = makeStore('Toko Asap');
    $this->owner = makeMember($this->store, 'owner');
});

it('renders a printable report for a user who may see reports', function () {
    webProduct($this->store, $this->owner, ['name' => 'Kopi Susu']);

    $response = $this->actingAs($this->owner)
        ->get(route('reports.print', ['preset' => '7d']))
        ->assertOk();

    $response->assertSee('Laporan penjualan')
        ->assertSee('Toko Asap')
        ->assertSee('Penjualan harian')
        ->assertSee('Metode bayar')
        ->assertSee('Inventori');

    // Bukan halaman Inertia: tidak boleh ada div#app yang menunggu Vue.
    expect($response->getContent())->not->toContain('data-page=');
});

it('follows the period asked for in the querystring', function () {
    $this->actingAs($this->owner)
        ->get(route('reports.print', ['preset' => 'custom', 'from' => '2026-01-01', 'to' => '2026-01-07']))
        ->assertOk()
        ->assertSee('2026-01-01 sampai 2026-01-07');
});

it('keeps a cashier out of the printable report', function () {
    $cashier = makeMember($this->store, 'cashier');

    $this->actingAs($cashier)->get(route('reports.print'))->assertForbidden();
    // Halaman laporannya sendiri juga tertutup — cetak bukan pintu belakang.
    $this->actingAs($cashier)->get(route('reports.index'))->assertForbidden();
});
