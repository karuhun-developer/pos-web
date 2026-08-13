<?php

use App\Models\User;
use Database\Seeders\SuperadminSeeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder ini yang membuat akun platform pertama, jadi dua hal dijaga: tanpa
 * password ia tidak boleh membuat apa pun, dan menjalankannya ulang tidak boleh
 * mengembalikan password lama akun yang sudah hidup.
 */
beforeEach(function () {
    seedRoles();

    config([
        'platform.superadmin.name' => 'Superadmin',
        'platform.superadmin.email' => 'boss@pospro.test',
        'platform.superadmin.password' => 'rahasia-panjang',
    ]);
});

it('creates a superadmin with a store to land on after login', function () {
    $this->seed(SuperadminSeeder::class);

    $user = User::query()->sole();

    expect($user->email)->toBe('boss@pospro.test')
        ->and($user->isSuperadmin())->toBeTrue()
        ->and(Hash::check('rahasia-panjang', $user->password))->toBeTrue()
        ->and($user->current_store_id)->not->toBeNull();
});

it('skips itself when no password is configured', function () {
    config(['platform.superadmin.password' => null]);

    $this->seed(SuperadminSeeder::class);

    expect(User::query()->count())->toBe(0);
});

it('keeps the existing password when run again', function () {
    $this->seed(SuperadminSeeder::class);

    User::query()->sole()->forceFill(['password' => Hash::make('sudah-diganti')])->save();

    $this->seed(SuperadminSeeder::class);

    $user = User::query()->sole();

    expect(Hash::check('sudah-diganti', $user->password))->toBeTrue()
        ->and($user->isSuperadmin())->toBeTrue();
});
