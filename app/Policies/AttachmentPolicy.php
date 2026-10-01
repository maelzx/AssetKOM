<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Uploaders can remove their own files; managers can remove any
     * (administrators pass via gate before).
     */
    public function delete(User $user, Attachment $attachment): bool
    {
        return $user->hasRole(Role::Manager) || $attachment->uploaded_by === $user->id;
    }
}
