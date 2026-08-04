<?php

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('public');
    $this->store = makeStore();
    Sanctum::actingAs(makeMember($this->store, 'owner'));
});

function mediaPayload(array $overrides = []): array
{
    return array_merge([
        'id' => 'm-1',
        'mime' => 'image/jpeg',
        'width' => 64,
        'height' => 64,
        'bytes' => 12,
        'hash' => 'abc123',
        'data' => base64_encode('fake-image-bytes'),
        'remote_url' => null,
        'created_at' => ms(),
        'updated_at' => ms(),
        'deleted_at' => null,
        'dirty' => 1,
        'sync_version' => 0,
        'remote_id' => null,
    ], $overrides);
}

it('stores base64 to disk and fills remote_url on push', function () {
    $this->postJson('/api/v1/sync/push', [
        'changes' => [envelope('media', 'insert', mediaPayload())],
    ])->assertOk();

    $row = Media::withoutGlobalScopes()->find('m-1');
    expect($row->remote_url)->not->toBeNull()
        ->and($row->data)->toBeNull(); // byte pindah ke storage

    Storage::disk('public')->assertExists('media/m-1.jpg');
});

it('returns remote_url and null data on pull', function () {
    $this->postJson('/api/v1/sync/push', [
        'changes' => [envelope('media', 'insert', mediaPayload())],
    ])->assertOk();

    $row = $this->getJson('/api/v1/sync/pull?entity=media&since=0')->json('changes.0');
    expect($row['data'])->toBeNull()
        ->and($row['remote_url'])->not->toBeNull();
});
