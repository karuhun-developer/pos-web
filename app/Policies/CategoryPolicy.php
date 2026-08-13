<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreOwnership;

class CategoryPolicy
{
    use ChecksStoreOwnership;

    public function viewAny(User $user): bool
    {
        return $this->memberOfCurrentStore($user);
    }

    public function view(User $user, Category $category): bool
    {
        return $this->ownsRow($user, $category);
    }

    public function create(User $user): bool
    {
        return $this->memberOfCurrentStore($user) && $user->can('catalog.manage');
    }

    public function update(User $user, Category $category): bool
    {
        return $this->ownsRow($user, $category) && $user->can('catalog.manage');
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->update($user, $category);
    }
}
