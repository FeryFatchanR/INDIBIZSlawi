<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;
use Filament\Forms\Components\Repeater;

class GallerySection extends SectionType
{
    public function key(): string
    {
        return 'gallery';
    }

    public function label(): string
    {
        return 'Image Gallery';
    }

    public function category(): string
    {
        return 'Media';
    }

    public function icon(): string
    {
        return 'heroicon-o-squares-2x2';
    }

    public function view(): string
    {
        return 'builder.gallery';
    }

    public function supportsColumns(): bool
    {
        return true;
    }

    public function defaultContent(): array
    {
        return ['heading' => null, 'images' => []];
    }

    public function contentRules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:200'],
            'images' => ['nullable', 'array', 'max:40'],
            'images.*.image_id' => ['required', 'integer', 'exists:media_assets,id'],
            'images.*.caption' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function mediaFields(): array
    {
        return ['images.*.image_id'];
    }

    public function contentFields(): array
    {
        return [
            Fields::text('heading', 'Judul (opsional)', 200),
            Repeater::make('images')->label('Gambar')->schema([
                Fields::media('image_id', 'Gambar')->required(),
                Fields::text('caption', 'Keterangan', 200),
            ])->maxItems(40)->collapsible()->reorderableWithButtons(),
        ];
    }
}
