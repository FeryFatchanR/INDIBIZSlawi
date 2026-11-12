<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;

class RichTextSection extends SectionType
{
    public function key(): string
    {
        return 'rich_text';
    }

    public function label(): string
    {
        return 'Rich Text';
    }

    public function category(): string
    {
        return 'Teks';
    }

    public function icon(): string
    {
        return 'heroicon-o-document-text';
    }

    public function view(): string
    {
        return 'builder.rich-text';
    }

    public function defaultContent(): array
    {
        return ['heading' => null, 'body' => '<p>Tulis konten di sini.</p>'];
    }

    public function defaultDesign(): array
    {
        return ['container' => 'narrow', 'padding_y' => 'md'];
    }

    public function contentRules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:50000'],
        ];
    }

    public function richTextFields(): array
    {
        return ['body'];
    }

    public function contentFields(): array
    {
        return [
            Fields::text('heading', 'Judul (opsional)', 200),
            Fields::richText('body', 'Isi'),
        ];
    }
}
