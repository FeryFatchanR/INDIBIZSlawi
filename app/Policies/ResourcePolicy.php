<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy dasar: memetakan ability Filament/Laravel ke permission "{resource}.{ability}".
 * Super Admin di-bypass lewat Gate::before di AppServiceProvider.
 */
abstract class ResourcePolicy
{
    abstract protected function resource(): string;

    public function viewAny(User $user): bool
    {
        return $user->can($this->resource().'.view');
    }

    public function view(User $user, Model $model): bool
    {
        return $user->can($this->resource().'.view');
    }

    public function create(User $user): bool
    {
        return $user->can($this->resource().'.create');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can($this->resource().'.update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can($this->resource().'.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can($this->resource().'.delete');
    }

    public function restore(User $user, Model $model): bool
    {
        return $user->can($this->resource().'.delete');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can($this->resource().'.delete');
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $user->can($this->resource().'.delete');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can($this->resource().'.delete');
    }

    public function reorder(User $user): bool
    {
        return $user->can($this->resource().'.update');
    }

    public function replicate(User $user, Model $model): bool
    {
        return $user->can($this->resource().'.create');
    }
}
