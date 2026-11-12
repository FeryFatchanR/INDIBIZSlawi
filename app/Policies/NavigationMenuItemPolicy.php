<?php

namespace App\Policies;

/** Item anak mengikuti hak ubah resource induknya. */
class NavigationMenuItemPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'navigation';
    }

    public function create(\App\Models\User $user): bool
    {
        return $user->can('navigation.update');
    }

    public function delete(\App\Models\User $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->can('navigation.update');
    }

    public function deleteAny(\App\Models\User $user): bool
    {
        return $user->can('navigation.update');
    }
}
