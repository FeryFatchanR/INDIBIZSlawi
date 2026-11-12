<?php

namespace App\Support;

use App\Builder\SectionRegistry;
use App\Models\MediaUsage;
use Illuminate\Database\Eloquent\Model;

/**
 * Mencatat media yang dipakai konten agar media aktif tidak terhapus (FK restrict + policy).
 */
final class MediaUsageTracker
{
    /** @param array<string, int|null|array<int>> $fieldMap field => media id(s) */
    public static function sync(Model $model, array $fieldMap, ?string $fieldPrefix = null): void
    {
        $prefix = $fieldPrefix ? $fieldPrefix.':' : '';
        $query = MediaUsage::query()->where('usable_type', $model->getMorphClass())->where('usable_id', $model->getKey());
        if ($prefix !== '') {
            $query->where('field', 'like', $prefix.'%');
        }
        $query->delete();

        $rows = [];
        foreach ($fieldMap as $field => $ids) {
            foreach (array_unique(array_filter((array) $ids, 'is_numeric')) as $id) {
                $rows[] = ['media_asset_id' => (int) $id, 'usable_type' => $model->getMorphClass(), 'usable_id' => $model->getKey(), 'field' => substr($prefix.$field, 0, 80)];
            }
        }

        if ($rows !== []) {
            $existing = \App\Models\MediaAsset::withTrashed()->whereIn('id', array_column($rows, 'media_asset_id'))->pluck('id')->all();
            $rows = array_values(array_filter($rows, fn ($r) => in_array($r['media_asset_id'], $existing, true)));
            MediaUsage::query()->insertOrIgnore($rows);
        }
    }

    /** @param array<int, array<string, mixed>> $sections */
    public static function syncSections(Model $page, array $sections, string $scope): void
    {
        $registry = app(SectionRegistry::class);
        $map = [];
        foreach ($sections as $section) {
            if (! $registry->has($section['type'] ?? '')) {
                continue;
            }
            foreach ($registry->get($section['type'])->mediaFields() as $path) {
                $ids = (array) data_get($section['content'] ?? [], $path);
                if ($ids !== []) {
                    $map[substr($section['uuid'], 0, 8).'.'.$path] = $ids;
                }
            }
        }

        self::sync($page, $map, $scope);
    }
}
