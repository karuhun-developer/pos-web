<?php

use App\Models\User;

beforeEach(fn () => seedRoles());

it('logs in with email/password and returns a token + store', function () {
    User::factory()->create([
        'email' => 'owner@toko.com',
        'password' => 'rahasia123',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'owner@toko.com',
        'password' => 'rahasia123',
    ])
        ->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id'], 'stores' => [['id', 'role']]])
        ->assertJsonPath('stores.0.role', 'owner');
});

it('rejects wrong credentials with 422', function () {
    User::factory()->create(['email' => 'owner@toko.com', 'password' => 'rahasia123']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'owner@toko.com',
        'password' => 'salah',
    ])->assertStatus(422);
});

it('lets an authenticated user fetch /auth/me and logout', function () {
    User::factory()->create(['email' => 'owner@toko.com', 'password' => 'rahasia123']);
    $token = $this->postJson('/api/v1/auth/login', [
        'email' => 'owner@toko.com', 'password' => 'rahasia123',
    ])->json('token');

    $this->withToken($token)->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonStructure(['user' => ['id'], 'stores']);

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();
});
