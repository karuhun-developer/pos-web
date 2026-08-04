<?php

use App\Actions\Sync\ApplyChange;
use App\Models\Product;
use App\Support\StoreContext;
use App\Sync\RejectReason;
use App\Sync\SyncRejection;

beforeEach(function () {
    $this->store = makeStore();
    StoreContext::set($this->store);
    $this->apply = app(ApplyChange::class);
});

afterEach(fn () => StoreContext::clear());

it('inserts a new row within the active store', function () {
    $this->apply->handle(envelope('products', 'insert', productPayload()));

    $row = Product::find('prod-1');
    expect($row)->not->toBeNull()
        ->and($row->store_id)->toBe($this->store->id)
        ->and($row->sync_version)->toBe(0);
});

it('throws Stale when incoming updated_at is not newer', function () {
    $this->apply->handle(envelope('products', 'insert', productPayload(['updated_at' => ms(100)])));

    expect(fn () => $this->apply->handle(
        envelope('products', 'update', productPayload(['updated_at' => ms(100)]))
    ))->toThrow(SyncRejection::class);

    try {
        $this->apply->handle(envelope('products', 'update', productPayload(['updated_at' => ms(50)])));
    } catch (SyncRejection $e) {
        expect($e->reason)->toBe(RejectReason::Stale);
    }
});

it('rejects entities outside the allowlist', function () {
    try {
        $this->apply->handle(envelope('widgets', 'insert', ['id' => 'w', 'updated_at' => ms()]));
        $this->fail('expected rejection');
    } catch (SyncRejection $e) {
        expect($e->reason)->toBe(RejectReason::UnknownEntity);
    }
});

it('rejects device-local entities as forbidden', function () {
    try {
        $this->apply->handle(envelope('settings', 'insert', ['id' => 's', 'updated_at' => ms()]));
        $this->fail('expected rejection');
    } catch (SyncRejection $e) {
        expect($e->reason)->toBe(RejectReason::ForbiddenEntity);
    }
});
