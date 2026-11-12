<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

/** Audit log bersifat read-only. */
class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('activity.view');
    }

    public function view(User $user, ActivityLog $log): bool
    {
        return $user->can('activity.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ActivityLog $log): bool
    {
        return false;
    }

    public function delete(User $user, ActivityLog $log): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
