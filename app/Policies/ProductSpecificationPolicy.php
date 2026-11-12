<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Item anak mengikuti hak ubah resource induknya. */
class ProductSpecificationPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'products';
    }

    public function create(User $user): bool
    {
        return $user->can('products.update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can('products.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('products.update');
    }
}
