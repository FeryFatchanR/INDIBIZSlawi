<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;

class DividerSection extends SectionType
{
    public function key(): string
    {
        return 'divider';
    }

    public function label(): string
    {
        return 'Divider';
    }

    public function category(): string
    {
        return 'Layout';
    }

    public function icon(): string
    {
        return 'heroicon-o-minus';
    }

    public function view(): string
    {
        return 'builder.divider';
    }

    public function defaultDesign(): array
    {
        return ['padding_y' => 'sm', 'line_style' => 'solid'];
    }

    public function contentRules(): array
    {
        return [];
    }

    public function designRules(): array
    {
        return ['line_style' => ['nullable', 'in:solid,dashed,dotted']];
    }

    public function contentFields(): array
    {
        return [];
    }

    public function designFields(): array
    {
        return [Fields::select('line_style', 'Gaya garis', ['solid' => 'Solid', 'dashed' => 'Putus-putus', 'dotted' => 'Titik-titik'])];
    }
}
