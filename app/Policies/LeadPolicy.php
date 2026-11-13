<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

/**
 * Akses lead: leads.view_all (semua) atau leads.view_assigned (hanya yang di-assign ke user).
 * Ini mencegah IDOR walaupun URL/UUID lead diketahui.
 */
class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leads.view_all') || $user->can('leads.view_assigned');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->can('leads.view_all')
            || ($user->can('leads.view_assigned') && $lead->assigned_to === $user->getKey());
    }

    public function create(User $user): bool
    {
        return $user->can('leads.create');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->can('leads.update') && $this->view($user, $lead);
    }

    public function changeStatus(User $user, Lead $lead): bool
    {
        return $this->update($user, $lead);
    }

    public function addNote(User $user, Lead $lead): bool
    {
        return $user->can('leads.add_note') && $this->view($user, $lead);
    }

    public function assign(User $user, ?Lead $lead = null): bool
    {
        return $user->can('leads.assign');
    }

    public function export(User $user): bool
    {
        return $user->can('leads.export');
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->can('leads.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('leads.delete');
    }

    public function restore(User $user, Lead $lead): bool
    {
        return $user->can('leads.delete');
    }

    public function forceDelete(User $user, Lead $lead): bool
    {
        return false; // data CRM hanya soft delete
    }
}
