<?php

namespace App\Rules;

use App\Support\UrlGuard;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** URL hanya boleh http(s), mailto, tel, path relatif "/..." atau anchor "#...". */
class SafeUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || ! UrlGuard::isSafe($value)) {
            $fail('URL :attribute tidak valid atau memakai protokol yang tidak diizinkan.');
        }
    }
}
