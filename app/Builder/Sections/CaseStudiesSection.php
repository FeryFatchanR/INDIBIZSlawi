<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;
use App\Models\CaseStudy;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class CaseStudiesSection extends SectionType
{
    public function key(): string
    {
        return 'case_studies';
    }

    public function label(): string
    {
        return 'Case Studies';
    }

    public function category(): string
    {
        return 'Kepercayaan';
    }

    public function icon(): string
    {
        return 'heroicon-o-document-chart-bar';
    }

    public function view(): string
    {
        return 'builder.case-studies';
    }

    public function supportsColumns(): bool
    {
        return true;
    }

    public function defaultContent(): array
    {
        return ['heading' => 'Studi Kasus', 'subheading' => null, 'limit' => 3];
    }

    public function contentRules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:200'],
            'subheading' => ['nullable', 'string', 'max:500'],
            'limit' => ['nullable', 'integer', 'between:1,12'],
        ];
    }

    public function contentFields(): array
    {
        return [
            Fields::text('heading', 'Judul', 200),
            Textarea::make('subheading')->label('Subjudul')->maxLength(500)->rows(2),
            TextInput::make('limit')->label('Jumlah')->numeric()->minValue(1)->maxValue(12),
        ];
    }

    public function prepare(array $content, array $design): array
    {
        return ['cases' => CaseStudy::query()->visible()->with(['image', 'industrySolution'])->limit((int) ($content['limit'] ?? 3))->get()];
    }
}
