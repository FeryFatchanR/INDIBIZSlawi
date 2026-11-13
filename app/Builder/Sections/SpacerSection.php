<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;

class SpacerSection extends SectionType
{
    public function key(): string
    {
        return 'spacer';
    }

    public function label(): string
    {
        return 'Spacer';
    }

    public function category(): string
    {
        return 'Layout';
    }

    public function icon(): string
    {
        return 'heroicon-o-arrows-up-down';
    }

    public function view(): string
    {
        return 'builder.spacer';
    }

    public function defaultDesign(): array
    {
        return ['padding_y' => 'md'];
    }

    public function contentRules(): array
    {
        return [];
    }

    public function contentFields(): array
    {
        return [Fields::text('note', 'Catatan internal (tidak tampil)', 80)->dehydrated(false)
            ->helperText('Atur tinggi spacer di tab Design (Padding vertikal) dan Responsive.')];
    }
}
