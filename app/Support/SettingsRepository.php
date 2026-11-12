<?php

namespace App\Support;

use App\Models\GlobalSetting;
use Illuminate\Support\Arr;

/**
 * Akses pengaturan website (group.key) dengan default dari config/cms.php dan cache ber-versi.
 */
class SettingsRepository
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $loaded = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return Arr::get($all, $key, $default ?? Arr::get(config('cms.settings_defaults', []), $key));
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        $stored = CmsCache::remember('settings', fn () => GlobalSetting::query()->get(['group', 'key', 'value'])
            ->groupBy('group')
            ->map(fn ($rows) => $rows->pluck('value', 'key')->all())
            ->all());

        return $this->loaded = array_replace_recursive(config('cms.settings_defaults', []), $stored);
    }

    /** @return array<string, mixed> */
    public function group(string $group): array
    {
        return $this->all()[$group] ?? [];
    }

    /** @param array<string, mixed> $values */
    public function setGroup(string $group, array $values): void
    {
        foreach ($values as $key => $value) {
            GlobalSetting::query()->updateOrCreate(['group' => $group, 'key' => $key], ['value' => $value]);
        }
        $this->flush();
    }

    public function flush(): void
    {
        $this->loaded = null;
        CmsCache::bump();
    }
}
