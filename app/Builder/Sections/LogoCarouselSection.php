<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;
use App\Models\Partner;

class LogoCarouselSection extends SectionType
{
    public function key(): string
    {
        return 'logo_carousel';
    }

    public function label(): string
    {
        return 'Logo Carousel';
    }

    public function category(): string
    {
        return 'Kepercayaan';
    }

    public function icon(): string
    {
        return 'heroicon-o-building-office-2';
    }

    public function view(): string
    {
        return 'builder.logo-carousel';
    }

    public function defaultContent(): array
    {
        return ['heading' => 'Dipercaya oleh berbagai institusi'];
    }

    public function defaultDesign(): array
    {
        return ['text_align' => 'center', 'padding_y' => 'md', 'animate' => true, 'grayscale' => true];
    }

    public function contentRules(): array
    {
        return ['heading' => ['nullable', 'string', 'max:200']];
    }

    public function designRules(): array
    {
        return ['animate' => ['nullable', 'boolean'], 'grayscale' => ['nullable', 'boolean']];
    }

    public function contentFields(): array
    {
        return [Fields::text('heading', 'Judul', 200)->helperText('Logo diambil dari menu Marketing → Partners.')];
    }

    public function designFields(): array
    {
        return [Fields::toggle('animate', 'Animasi bergulir'), Fields::toggle('grayscale', 'Logo abu-abu (warna saat hover)')];
    }

    public function prepare(array $content, array $design): array
    {
        return ['partners' => Partner::query()->visible()->with('logo')->get()];
    }
}
