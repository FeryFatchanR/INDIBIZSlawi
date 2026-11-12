<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;
use App\Models\IndustrySolution;
use Filament\Forms\Components\Textarea;

class IndustrySolutionsSection extends SectionType
{
    public function key(): string
    {
        return 'industry_solutions';
    }

    public function label(): string
    {
        return 'Industry Solutions';
    }

    public function category(): string
    {
        return 'Produk & Penjualan';
    }

    public function icon(): string
    {
        return 'heroicon-o-briefcase';
    }

    public function view(): string
    {
        return 'builder.industry-solutions';
    }

    public function supportsColumns(): bool
    {
        return true;
    }

    public function defaultColumns(): array
    {
        return ['desktop' => 4, 'tablet' => 2, 'mobile' => 1];
    }

    public function defaultContent(): array
    {
        return ['heading' => 'Solusi untuk Setiap Industri', 'subheading' => null];
    }

    public function defaultDesign(): array
    {
        return ['text_align' => 'center'];
    }

    public function contentRules(): array
    {
        return ['heading' => ['nullable', 'string', 'max:200'], 'subheading' => ['nullable', 'string', 'max:500']];
    }

    public function contentFields(): array
    {
        return [
            Fields::text('heading', 'Judul', 200),
            Textarea::make('subheading')->label('Subjudul')->maxLength(500)->rows(2)
                ->helperText('Daftar industri dikelola di menu Marketing → Industry Solutions.'),
        ];
    }

    public function prepare(array $content, array $design): array
    {
        return ['solutions' => IndustrySolution::query()->visible()->get()];
    }
}
