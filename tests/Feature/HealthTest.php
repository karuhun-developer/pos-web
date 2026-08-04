<?php

it('exposes health without auth', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertJson(['status' => 'ok', 'version' => 'v1']);
});
