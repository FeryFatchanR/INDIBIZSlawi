<?php

namespace App\Support;

/** Pengecekan izin user aktif yang aman bila tidak ada user terautentikasi (selalu false). */
final class Access
{
    public static function can(string $ability, mixed $arguments = []): bool
    {
        return (bool) auth()->user()?->can($ability, $arguments);
    }
}
