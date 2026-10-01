<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Manager);
    }

    public function view(User $user, Location $location): bool
    {
        return $user->hasRole(Role::Manager);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Manager);
    }

    public function update(User $user, Location $location): bool
    {
        return $user->hasRole(Role::Manager);
    }

    public function delete(User $user, Location $location): bool
    {
        return $user->hasRole(Role::Manager);
    }
}
