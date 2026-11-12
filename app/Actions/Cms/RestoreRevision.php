<?php

namespace App\Actions\Cms;

use App\Builder\SectionValidator;
use App\Enums\RevisionKind;
use App\Models\ActivityLog;
use App\Models\Page;
use App\Models\PageRevision;
use App\Models\PageSection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mengembalikan isi revision ke DRAFT. Versi publik tidak berubah sampai dipublish ulang.
 */
class RestoreRevision
{
    public function __construct(private readonly SectionValidator $validator) {}

    public function execute(Page $page, PageRevision $revision, ?int $expectedLockVersion, ?User $user): Page
    {
        if ($revision->page_id !== $page->id) {
            throw ValidationException::withMessages(['revision' => 'Revision bukan milik halaman ini.']);
        }

        $normalized = $this->validator->validateTree($revision->sections());

        return DB::transaction(function () use ($page, $revision, $normalized, $expectedLockVersion, $user) {
            $locked = PageSnapshot::lock($page, $expectedLockVersion);

            PageSection::query()->where('page_id', $locked->id)->delete();
            foreach ($normalized as $section) {
                PageSection::query()->create($section + ['page_id' => $locked->id]);
            }

            $meta = $revision->snapshot['page'] ?? [];
            $locked->forceFill([
                'title' => $meta['title'] ?? $locked->title,
                'meta_title' => $meta['meta_title'] ?? null,
                'meta_description' => $meta['meta_description'] ?? null,
                'lock_version' => $locked->lock_version + 1,
                'updated_by' => $user?->id,
            ])->save();

            PageSnapshot::createRevision($locked, RevisionKind::Restore, PageSnapshot::build($locked, $normalized), $user,
                "Dipulihkan dari revision #{$revision->version}");
            ActivityLog::record('page.revision_restored', $locked, null, ['from_version' => $revision->version]);

            return $locked->fresh();
        });
    }
}
