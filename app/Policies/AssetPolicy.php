<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    /**
     * Everyone can browse the asset registry (staff are read-only).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Asset $asset): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Manager);
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->hasRole(Role::Manager);
    }

    /**
     * Only administrators may delete assets (gate before grants admins).
     */
    public function delete(User $user, Asset $asset): bool
    {
        return false;
    }

    public function restore(User $user, Asset $asset): bool
    {
        return false;
    }

    public function forceDelete(User $user, Asset $asset): bool
    {
        return false;
    }
}
