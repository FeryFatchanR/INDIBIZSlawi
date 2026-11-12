<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;
use Filament\Forms\Components\Textarea;

class HeadingSection extends SectionType
{
    public function key(): string
    {
        return 'heading';
    }

    public function label(): string
    {
        return 'Heading';
    }

    public function category(): string
    {
        return 'Teks';
    }

    public function icon(): string
    {
        return 'heroicon-o-h1';
    }

    public function view(): string
    {
        return 'builder.heading';
    }

    public function defaultContent(): array
    {
        return ['eyebrow' => null, 'heading' => 'Judul Section', 'subheading' => null, 'level' => 'h2'];
    }

    public function defaultDesign(): array
    {
        return ['text_align' => 'center', 'padding_y' => 'md'];
    }

    public function contentRules(): array
    {
        return [
            'eyebrow' => ['nullable', 'string', 'max:80'],
            'heading' => ['required', 'string', 'max:200'],
            'subheading' => ['nullable', 'string', 'max:500'],
            'level' => ['required', 'in:h1,h2,h3'],
        ];
    }

    public function contentFields(): array
    {
        return [
            Fields::text('eyebrow', 'Label kecil', 80),
            Fields::text('heading', 'Judul', 200)->required(),
            Textarea::make('subheading')->label('Subjudul')->maxLength(500)->rows(2),
            Fields::select('level', 'Level heading (SEO)', ['h1' => 'H1 (satu per halaman)', 'h2' => 'H2', 'h3' => 'H3']),
        ];
    }
}
