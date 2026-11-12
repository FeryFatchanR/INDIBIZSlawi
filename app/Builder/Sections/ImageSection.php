<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;

class ImageSection extends SectionType
{
    public function key(): string
    {
        return 'image';
    }

    public function label(): string
    {
        return 'Image';
    }

    public function category(): string
    {
        return 'Media';
    }

    public function icon(): string
    {
        return 'heroicon-o-photo';
    }

    public function view(): string
    {
        return 'builder.image';
    }

    public function defaultContent(): array
    {
        return ['image_id' => null, 'alt' => null, 'caption' => null, 'link_url' => null];
    }

    public function defaultDesign(): array
    {
        return ['rounded' => true, 'padding_y' => 'md'];
    }

    public function contentRules(): array
    {
        return [
            'image_id' => $this->mediaRule(),
            'alt' => ['nullable', 'string', 'max:200'],
            'caption' => ['nullable', 'string', 'max:300'],
            'link_url' => ['nullable', 'string', 'max:2048', new \App\Rules\SafeUrl],
        ];
    }

    public function designRules(): array
    {
        return ['rounded' => ['nullable', 'boolean'], 'shadow' => ['nullable', 'boolean']];
    }

    public function mediaFields(): array
    {
        return ['image_id'];
    }

    public function contentFields(): array
    {
        return [
            Fields::media('image_id', 'Gambar'),
            Fields::text('alt', 'Alt text (aksesibilitas & SEO)', 200)->helperText('Kosongkan untuk memakai alt dari Media Library.'),
            Fields::text('caption', 'Keterangan', 300),
            Fields::url('link_url', 'Tautan saat diklik (opsional)'),
        ];
    }

    public function designFields(): array
    {
        return [Fields::toggle('rounded', 'Sudut membulat'), Fields::toggle('shadow', 'Bayangan')];
    }
}
