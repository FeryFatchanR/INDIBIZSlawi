<?php

namespace App\Actions\Cms;

use App\Enums\RevisionKind;
use App\Exceptions\StaleDraftException;
use App\Models\Page;
use App\Models\PageRevision;
use App\Models\User;

/** Utilitas bersama Action CMS: kunci baris, cek versi, buat revision. */
final class PageSnapshot
{
    /** Ambil ulang page dengan row lock (SELECT ... FOR UPDATE) dan validasi lock_version. */
    public static function lock(Page $page, ?int $expectedLockVersion): Page
    {
        /** @var Page $locked */
        $locked = Page::query()->whereKey($page->getKey())->lockForUpdate()->firstOrFail();

        if ($expectedLockVersion !== null && $locked->lock_version !== $expectedLockVersion) {
            throw new StaleDraftException($expectedLockVersion, $locked->lock_version);
        }

        return $locked;
    }

    /** @return array<string, mixed> */
    public static function build(Page $page, array $sections): array
    {
        return [
            'lock_version' => $page->lock_version,
            'page' => [
                'title' => $page->title,
                'slug' => $page->slug,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
                'canonical_url' => $page->canonical_url,
                'og_image_id' => $page->og_image_id,
                'noindex' => $page->noindex,
            ],
            'sections' => array_values($sections),
        ];
    }

    public static function createRevision(Page $page, RevisionKind $kind, array $snapshot, ?User $user, ?string $note = null): PageRevision
    {
        $version = (int) PageRevision::query()->where('page_id', $page->id)->max('version') + 1;

        $revision = PageRevision::query()->create([
            'page_id' => $page->id,
            'version' => $version,
            'kind' => $kind,
            'note' => $note,
            'snapshot' => $snapshot,
            'created_by' => $user?->id,
        ]);

        if ($kind === RevisionKind::Draft) {
            self::pruneDraftRevisions($page);
        }

        return $revision;
    }

    /** Simpan maksimal N revision draft terbaru; revision publish/restore tidak pernah dipangkas. */
    private static function pruneDraftRevisions(Page $page): void
    {
        $keep = (int) config('cms.builder.max_draft_revisions', 50);
        $ids = PageRevision::query()->where('page_id', $page->id)->where('kind', RevisionKind::Draft)
            ->where('id', '!=', (int) $page->published_revision_id)
            ->orderByDesc('version')->skip($keep)->take(1000)->pluck('id');

        if ($ids->isNotEmpty()) {
            PageRevision::query()->whereIn('id', $ids)->delete();
        }
    }
}
