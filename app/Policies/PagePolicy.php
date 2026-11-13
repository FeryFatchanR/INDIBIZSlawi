<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PagePolicy extends ResourcePolicy
{
    protected function resource(): string
    {
        return 'pages';
    }

    /** Membuka visual builder & mengubah draft section. */
    public function build(User $user, Page $page): bool
    {
        return $user->can('pages.update') && $user->can('builder.edit');
    }

    public function publish(User $user, Page $page): bool
    {
        return $user->can('pages.publish');
    }

    public function restoreRevision(User $user, Page $page): bool
    {
        return $user->can('pages.restore') && $user->can('pages.update');
    }

    /** Preview draft hanya untuk pengguna panel yang boleh melihat halaman. */
    public function preview(User $user, Page $page): bool
    {
        return $user->can('pages.view');
    }

    /** Homepage tidak boleh dihapus agar situs tidak kosong. */
    public function delete(User $user, Model $model): bool
    {
        /** @var Page $model */
        return parent::delete($user, $model) && ! $model->isHome();
    }
}
