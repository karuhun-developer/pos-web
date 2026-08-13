<?php

use App\Support\DonationSettings;
use App\Support\PlatformSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

/**
 * Cara berdonasi diatur superadmin lewat panel, bukan lewat .env — jadi yang
 * dijaga di sini: hanya superadmin yang boleh mengubahnya, dan yang tersimpan
 * benar-benar sampai ke halaman publik.
 */
it('lets a superadmin set the payment targets and shows them on the public page', function () {
    Storage::fake(DonationSettings::DISK);

    $this->actingAs(makeSuperadmin())
        ->post(route('admin.donations.settings.update'), [
            'qris' => UploadedFile::fake()->image('qris.png'),
            'banks' => [
                ['bank' => 'BCA', 'account_number' => '1234567890', 'account_name' => 'Yayasan Uji'],
                // Baris kosong bawaan formulir tidak boleh ikut tersimpan.
                ['bank' => '', 'account_number' => '', 'account_name' => ''],
            ],
            'saweria_url' => 'https://saweria.co/uji',
            'note' => 'Buat bayar server.',
        ])
        ->assertRedirect();

    PlatformSettings::flush();
    $settings = DonationSettings::all();

    expect($settings['banks'])->toHaveCount(1)
        ->and($settings['qris_path'])->not->toBeNull()
        ->and(DonationSettings::channels())->toBe(['qris', 'transfer', 'saweria']);

    Storage::disk(DonationSettings::DISK)->assertExists($settings['qris_path']);

    $this->get(route('donate.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pay.note', 'Buat bayar server.')
            ->where('pay.saweria_url', 'https://saweria.co/uji')
            ->where('pay.banks.0.bank', 'BCA'));
});

it('deletes the old qris image when it is replaced', function () {
    Storage::fake(DonationSettings::DISK);

    $this->actingAs(makeSuperadmin())
        ->post(route('admin.donations.settings.update'), ['qris' => UploadedFile::fake()->image('lama.png')])
        ->assertRedirect();

    PlatformSettings::flush();
    $old = DonationSettings::all()['qris_path'];

    $this->actingAs(makeSuperadmin())
        ->post(route('admin.donations.settings.update'), ['qris' => UploadedFile::fake()->image('baru.png')])
        ->assertRedirect();

    PlatformSettings::flush();

    expect(DonationSettings::all()['qris_path'])->not->toBe($old);
    Storage::disk(DonationSettings::DISK)->assertMissing($old);
});

it('refuses a non-superadmin', function () {
    $user = makeMember(makeStore());

    $this->actingAs($user)->get(route('admin.donations.settings'))->assertForbidden();

    $this->actingAs($user)
        ->post(route('admin.donations.settings.update'), ['saweria_url' => 'https://saweria.co/nakal'])
        ->assertForbidden();

    expect(DonationSettings::all()['saweria_url'])->toBeNull();
});
