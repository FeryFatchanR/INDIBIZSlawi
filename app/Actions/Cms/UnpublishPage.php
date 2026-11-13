<?php

namespace App\Actions\Cms;

use App\Enums\PageStatus;
use App\Models\ActivityLog;
use App\Models\Page;
use App\Models\User;
use App\Support\CmsCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UnpublishPage
{
    public function execute(Page $page, ?User $user): Page
    {
        if ($page->isHome()) {
            throw ValidationException::withMessages(['page' => 'Homepage tidak dapat di-unpublish.']);
        }

        return DB::transaction(function () use ($page) {
            $locked = PageSnapshot::lock($page, null);
            $locked->forceFill(['status' => PageStatus::Unpublished])->save();
            ActivityLog::record('page.unpublished', $locked);
            DB::afterCommit(fn () => CmsCache::bump());

            return $locked->fresh();
        });
    }
}
