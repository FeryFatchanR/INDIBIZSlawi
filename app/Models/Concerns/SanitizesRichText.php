<?php

namespace App\Models\Concerns;

use App\Support\HtmlSanitizer;

/** Sanitasi atribut rich text (allowlist) setiap kali model disimpan. */
trait SanitizesRichText
{
    public static function bootSanitizesRichText(): void
    {
        static::saving(function ($model) {
            foreach ($model->richTextAttributes ?? [] as $attribute) {
                if ($model->isDirty($attribute) && is_string($model->{$attribute})) {
                    $model->{$attribute} = HtmlSanitizer::richText($model->{$attribute});
                }
            }
        });
    }
}
