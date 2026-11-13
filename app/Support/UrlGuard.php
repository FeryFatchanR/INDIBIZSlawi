<?php

namespace App\Support;

final class UrlGuard
{
    public static function isSafe(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 2048 || preg_match('/[\x00-\x1F\x7F\s]/', $url)) {
            return false;
        }

        // Path relatif (bukan protocol-relative "//host") atau anchor.
        if (str_starts_with($url, '#') || (str_starts_with($url, '/') && ! str_starts_with($url, '//'))) {
            return true;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return match ($scheme) {
            'http', 'https' => filter_var($url, FILTER_VALIDATE_URL) !== false && parse_url($url, PHP_URL_HOST),
            'mailto' => filter_var(substr($url, 7), FILTER_VALIDATE_EMAIL) !== false,
            'tel' => (bool) preg_match('/^tel:\+?[0-9\-\s()]{5,20}$/', $url),
            default => false,
        };
    }

    /** Kembalikan URL aman atau fallback ('#'). Dipakai saat render sebagai lapisan kedua. */
    public static function sanitize(?string $url, string $fallback = '#'): string
    {
        return $url !== null && self::isSafe($url) ? trim($url) : $fallback;
    }

    public static function isExternal(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host !== null && $host !== false && $host !== parse_url((string) config('app.url'), PHP_URL_HOST);
    }
}
