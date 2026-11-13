<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/** Mengisi kolom `uuid` otomatis saat model dibuat. */
trait HasUuid
{
    public static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
