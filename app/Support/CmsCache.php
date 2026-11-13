<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cache konten publik dengan kunci ber-versi. Menaikkan versi = invalidasi seluruh cache CMS
 * tanpa butuh cache tags (kompatibel dengan driver database/file).
 */
final class CmsCache
{
    private const VERSION_KEY = 'cms:version';

    public static function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
    }

    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    public static function key(string $suffix): string
    {
        return 'cms:v'.self::version().':'.$suffix;
    }

    public static function remember(string $suffix, Closure $callback, int $ttl = 86400): mixed
    {
        return Cache::remember(self::key($suffix), $ttl, $callback);
    }
}
