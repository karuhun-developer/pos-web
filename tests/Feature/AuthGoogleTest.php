<?php

use App\Contracts\GoogleTokenVerifier;
use App\Models\User;
use Tests\Support\FakeGoogleVerifier;

beforeEach(function () {
    seedRoles();
    app()->bind(GoogleTokenVerifier::class, FakeGoogleVerifier::class);
});

it('logs in with a valid google id token and provisions a store', function () {
    $res = $this->postJson('/api/v1/auth/google', [
        'id_token' => 'valid:g-123:budi@toko.com',
    ])->assertOk();

    $res->assertJsonStructure([
        'token',
        'user' => ['id', 'name', 'email', 'avatar_url', 'current_store_id'],
        'stores' => [['id', 'name', 'role']],
    ]);

    $user = User::where('email', 'budi@toko.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->google_id)->toBe('g-123')
        ->and($user->current_store_id)->not->toBeNull()
        ->and($res->json('stores.0.role'))->toBe('owner');
});

it('rejects an invalid google id token with 422', function () {
    $this->postJson('/api/v1/auth/google', ['id_token' => 'garbage'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('id_token');
});

it('reuses the existing user on repeated google login', function () {
    $payload = ['id_token' => 'valid:g-123:budi@toko.com'];
    $this->postJson('/api/v1/auth/google', $payload)->assertOk();
    $this->postJson('/api/v1/auth/google', $payload)->assertOk();

    expect(User::where('email', 'budi@toko.com')->count())->toBe(1);
});
