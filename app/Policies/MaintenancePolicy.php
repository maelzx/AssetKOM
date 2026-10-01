<?php

namespace App\Policies;

use App\Models\Maintenance;
use App\Models\User;

class MaintenancePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Maintenance $maintenance): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Maintenance $maintenance): bool
    {
        return true;
    }

    /**
     * Only administrators may delete maintenance records (gate before grants admins).
     */
    public function delete(User $user, Maintenance $maintenance): bool
    {
        return false;
    }
}
