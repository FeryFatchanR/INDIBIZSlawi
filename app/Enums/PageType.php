<?php

namespace App\Enums;

enum PageType: string
{
    case Page = 'page';
    case Home = 'home';
    case Global = 'global';

    public function label(): string
    {
        return match ($this) {
            self::Page => 'Halaman',
            self::Home => 'Homepage',
            self::Global => 'Global Section',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
