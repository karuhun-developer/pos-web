<?php

namespace App\Policies;

use App\Models\CashflowEntry;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreOwnership;

class CashflowEntryPolicy
{
    use ChecksStoreOwnership;

    public function viewAny(User $user): bool
    {
        return $this->memberOfCurrentStore($user);
    }

    public function view(User $user, CashflowEntry $entry): bool
    {
        return $this->ownsRow($user, $entry);
    }

    public function create(User $user): bool
    {
        return $this->memberOfCurrentStore($user) && $user->can('cashflow.manage');
    }

    public function update(User $user, CashflowEntry $entry): bool
    {
        return $this->ownsRow($user, $entry) && $user->can('cashflow.manage');
    }

    public function delete(User $user, CashflowEntry $entry): bool
    {
        return $this->update($user, $entry);
    }
}
