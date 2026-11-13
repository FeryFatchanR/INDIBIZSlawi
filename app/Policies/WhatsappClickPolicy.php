<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WhatsappClick;

class WhatsappClickPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leads.reports');
    }

    public function view(User $user, WhatsappClick $click): bool
    {
        return $user->can('leads.reports');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, WhatsappClick $click): bool
    {
        return false;
    }

    public function delete(User $user, WhatsappClick $click): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
