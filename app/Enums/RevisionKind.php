<?php

namespace App\Enums;

enum RevisionKind: string
{
    case Draft = 'draft';
    case Publish = 'publish';
    case Restore = 'restore';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Publish => 'Publish',
            self::Restore => 'Restore',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
