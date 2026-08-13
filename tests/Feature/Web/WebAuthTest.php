<?php

use App\Models\Store;
use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery\MockInterface;

beforeEach(function () {
    seedRoles();
});

it('registers a user and gives them a first store', function () {
    $this->post(route('register'), [
        'name' => 'Ani',
        'email' => 'ani@contoh.test',
        'password' => 'rahasia-sekali',
        'password_confirmation' => 'rahasia-sekali',
    ])->assertRedirect(route('dashboard'));

    $user = User::where('email', 'ani@contoh.test')->sole();

    $this->assertAuthenticatedAs($user);

    // EnsureUserHasStore: tanpa toko pertama, seluruh route bertanda `store`
    // akan 403 dan user baru langsung menabrak tembok.
    expect($user->current_store_id)->not->toBeNull()
        ->and($user->stores()->count())->toBe(1);
});

it('logs a user in with their password', function () {
    $user = User::factory()->create([
        'email' => 'ani@contoh.test',
        'password' => 'rahasia-sekali',
    ]);

    $this->post(route('login'), [
        'email' => 'ani@contoh.test',
        'password' => 'rahasia-sekali',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('refuses a wrong password without saying which field was wrong', function () {
    User::factory()->create(['email' => 'ani@contoh.test', 'password' => 'rahasia-sekali']);

    $this->post(route('login'), [
        'email' => 'ani@contoh.test',
        'password' => 'salah',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('throttles repeated login attempts', function () {
    User::factory()->create(['email' => 'ani@contoh.test', 'password' => 'rahasia-sekali']);

    foreach (range(1, 5) as $ignored) {
        $this->post(route('login'), ['email' => 'ani@contoh.test', 'password' => 'salah']);
    }

    // Percobaan ke-6 ditolak sebelum password-nya sempat dicek, jadi form ini
    // tidak bisa dipakai menebak password satu per satu.
    $this->post(route('login'), ['email' => 'ani@contoh.test', 'password' => 'rahasia-sekali'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('logs out and clears the session', function () {
    $store = makeStore();
    $user = makeMember($store, 'owner');

    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('home'));

    $this->assertGuest();
});

it('signs a new user in through google oauth', function () {
    config()->set('services.google.client_secret', 'rahasia-google');

    fakeSocialite('google-123', 'ani@contoh.test', 'Ani');

    $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

    $user = User::where('email', 'ani@contoh.test')->sole();

    $this->assertAuthenticatedAs($user);
    expect($user->google_id)->toBe('google-123')
        ->and($user->stores()->count())->toBe(1);
});

it('links google to an account that already registered with a password', function () {
    config()->set('services.google.client_secret', 'rahasia-google');

    $existing = User::factory()->create([
        'email' => 'ani@contoh.test',
        'password' => 'rahasia-sekali',
    ]);
    Store::factory()->create(['owner_id' => $existing->id]);

    fakeSocialite('google-123', 'ani@contoh.test', 'Ani');

    $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

    // Satu email = satu user: OAuth tidak boleh membuat akun kedua yang
    // datanya terpisah dari toko yang sudah ada.
    expect(User::where('email', 'ani@contoh.test')->count())->toBe(1)
        ->and($existing->fresh()->google_id)->toBe('google-123');
});

it('sends the user back to login when google fails', function () {
    config()->set('services.google.client_secret', 'rahasia-google');

    Socialite::shouldReceive('driver->user')->andThrow(new RuntimeException('invalid state'));

    $this->get(route('google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    $this->assertGuest();
});

it('hides the google route when no client secret is configured', function () {
    config()->set('services.google.client_secret', null);

    $this->get(route('google.redirect'))->assertNotFound();
    $this->get(route('google.callback'))->assertNotFound();
});

/** Socialite palsu dengan klaim seperlunya (Socialite tidak punya ::fake()). */
function fakeSocialite(string $id, string $email, string $name): void
{
    $socialiteUser = Mockery::mock(SocialiteUser::class, function (MockInterface $mock) use ($id, $email, $name) {
        $mock->shouldReceive('getId')->andReturn($id);
        $mock->shouldReceive('getEmail')->andReturn($email);
        $mock->shouldReceive('getName')->andReturn($name);
        $mock->shouldReceive('getAvatar')->andReturn('https://lh3.google.test/foto.jpg');
    });

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);
}
