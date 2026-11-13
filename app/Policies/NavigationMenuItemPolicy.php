<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Item anak mengikuti hak ubah resource induknya. */
class NavigationMenuItemPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'navigation';
    }

    public function create(User $user): bool
    {
        return $user->can('navigation.update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can('navigation.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('navigation.update');
    }
}
