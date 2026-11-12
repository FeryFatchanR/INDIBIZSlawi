<?php

namespace App\Policies;

/** Item anak mengikuti hak ubah resource induknya. */
class ProductGalleryPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'products';
    }

    public function create(\App\Models\User $user): bool
    {
        return $user->can('products.update');
    }

    public function delete(\App\Models\User $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->can('products.update');
    }

    public function deleteAny(\App\Models\User $user): bool
    {
        return $user->can('products.update');
    }
}
