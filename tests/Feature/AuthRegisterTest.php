<?php

use App\Models\User;

beforeEach(fn () => seedRoles());

it('registers a new account and returns a token + owner store', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Sari Warung',
        'email' => 'sari@warung.com',
        'password' => 'rahasia123',
    ])
        ->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id'], 'stores' => [['id', 'role']]])
        ->assertJsonPath('stores.0.role', 'owner');

    expect(User::where('email', 'sari@warung.com')->exists())->toBeTrue();
});

it('rejects registration with a duplicate email (422)', function () {
    User::factory()->create(['email' => 'sari@warung.com']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Sari Lagi',
        'email' => 'sari@warung.com',
        'password' => 'rahasia123',
    ])->assertStatus(422)->assertJsonValidationErrorFor('email');
});

it('requires name, email, and password', function () {
    $this->postJson('/api/v1/auth/register', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});
