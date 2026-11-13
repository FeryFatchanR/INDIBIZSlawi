<?php

namespace App\Policies;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MediaAssetPolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'media';
    }

    /** Media yang masih dipakai konten tidak boleh dihapus. */
    public function delete(User $user, Model $model): bool
    {
        /** @var MediaAsset $model */
        return parent::delete($user, $model) && ! $model->isInUse();
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $this->delete($user, $model);
    }
}
