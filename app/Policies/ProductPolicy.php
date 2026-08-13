<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreOwnership;

class ProductPolicy
{
    use ChecksStoreOwnership;

    public function viewAny(User $user): bool
    {
        return $this->memberOfCurrentStore($user);
    }

    public function view(User $user, Product $product): bool
    {
        return $this->ownsRow($user, $product);
    }

    public function create(User $user): bool
    {
        return $this->memberOfCurrentStore($user) && $user->can('catalog.manage');
    }

    public function update(User $user, Product $product): bool
    {
        return $this->ownsRow($user, $product) && $user->can('catalog.manage');
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }
}
