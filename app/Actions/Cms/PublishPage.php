<?php

namespace App\Actions\Cms;

use App\Builder\SectionValidator;
use App\Enums\PageStatus;
use App\Enums\RevisionKind;
use App\Models\ActivityLog;
use App\Models\Page;
use App\Models\User;
use App\Support\CmsCache;
use App\Support\MediaUsageTracker;
use Illuminate\Support\Facades\DB;

/**
 * Publish atomik: validasi ulang draft → snapshot revision → arahkan halaman publik ke revision itu.
 * Bila salah satu langkah gagal, seluruh perubahan di-rollback dan versi publik lama tetap tampil.
 */
class PublishPage
{
    public function __construct(private readonly SectionValidator $validator) {}

    public function execute(Page $page, ?int $expectedLockVersion, ?User $user, ?string $note = null): Page
    {
        return DB::transaction(function () use ($page, $expectedLockVersion, $user, $note) {
            $locked = PageSnapshot::lock($page, $expectedLockVersion);

            $sections = $locked->sections()->get()->map->toBuilderArray()->all();
            // Validasi ulang tanpa cek permission per-komponen: konten sudah divalidasi saat disimpan
            // oleh penulisnya; sanitasi tetap dijalankan ulang sebagai lapisan pertahanan kedua.
            $normalized = $this->validator->validateTree($sections);

            $revision = PageSnapshot::createRevision($locked, RevisionKind::Publish, PageSnapshot::build($locked, $normalized), $user, $note);

            $locked->forceFill([
                'status' => PageStatus::Published,
                'published_revision_id' => $revision->id,
                'published_at' => now(),
                'published_by' => $user?->id,
            ])->save();

            MediaUsageTracker::syncSections($locked, $normalized, 'published');
            ActivityLog::record('page.published', $locked, null, ['revision' => $revision->version]);

            DB::afterCommit(fn () => CmsCache::bump());

            return $locked->fresh(['publishedRevision']);
        });
    }
}
