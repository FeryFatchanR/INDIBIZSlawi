<?php

namespace App\Actions\Cms;

use App\Builder\SectionValidator;
use App\Enums\RevisionKind;
use App\Models\ActivityLog;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\User;
use App\Support\MediaUsageTracker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Menyimpan seluruh tree section sebagai draft kerja. Versi publik TIDAK berubah.
 * Optimistic locking: gagal (StaleDraftException) jika lock_version berbeda.
 */
class SaveDraft
{
    public function __construct(private readonly SectionValidator $validator) {}

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @return Page page segar dengan lock_version baru
     */
    public function execute(Page $page, array $sections, ?int $expectedLockVersion, ?User $user, bool $checkpoint = false, ?string $note = null): Page
    {
        $sections = array_values($sections);
        foreach ($sections as $i => $section) {
            $sections[$i]['sort_order'] = $i;
        }

        $normalized = $this->validator->validateTree($sections, $user);

        return DB::transaction(function () use ($page, $normalized, $expectedLockVersion, $user, $checkpoint, $note) {
            $locked = PageSnapshot::lock($page, $expectedLockVersion);

            $uuids = array_column($normalized, 'uuid');
            $foreign = PageSection::query()->whereIn('uuid', $uuids)->where('page_id', '!=', $locked->id)->exists();
            if ($foreign) {
                throw ValidationException::withMessages(['sections' => 'UUID section sudah dipakai halaman lain.']);
            }

            PageSection::query()->where('page_id', $locked->id)->whereNotIn('uuid', $uuids)->delete();

            foreach ($normalized as $section) {
                PageSection::query()->updateOrCreate(
                    ['page_id' => $locked->id, 'uuid' => $section['uuid']],
                    collect($section)->except('uuid')->all(),
                );
            }

            $locked->forceFill(['lock_version' => $locked->lock_version + 1, 'updated_by' => $user?->id])->save();

            if ($checkpoint) {
                PageSnapshot::createRevision($locked, RevisionKind::Draft, PageSnapshot::build($locked, $normalized), $user, $note);
                ActivityLog::record('page.draft_saved', $locked, null, ['lock_version' => $locked->lock_version]);
            }

            MediaUsageTracker::syncSections($locked, $normalized, 'draft');

            return $locked->fresh();
        });
    }
}
