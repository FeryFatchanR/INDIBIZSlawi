<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;
use App\Models\Statistic;
use Filament\Forms\Components\Repeater;

class StatisticsSection extends SectionType
{
    public function key(): string
    {
        return 'statistics';
    }

    public function label(): string
    {
        return 'Statistics';
    }

    public function category(): string
    {
        return 'Kepercayaan';
    }

    public function icon(): string
    {
        return 'heroicon-o-chart-bar';
    }

    public function view(): string
    {
        return 'builder.statistics';
    }

    public function supportsColumns(): bool
    {
        return true;
    }

    public function defaultColumns(): array
    {
        return ['desktop' => 3, 'tablet' => 3, 'mobile' => 1];
    }

    public function defaultContent(): array
    {
        return ['heading' => null, 'source' => 'database', 'items' => []];
    }

    public function defaultDesign(): array
    {
        return ['text_align' => 'center', 'background' => 'primary', 'padding_y' => 'md'];
    }

    public function contentRules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:200'],
            'source' => ['required', 'in:database,manual'],
            'items' => ['nullable', 'array', 'max:12'],
            'items.*.value' => ['required', 'string', 'max:20'],
            'items.*.suffix' => ['nullable', 'string', 'max:10'],
            'items.*.label' => ['required', 'string', 'max:80'],
        ];
    }

    public function contentFields(): array
    {
        return [
            Fields::text('heading', 'Judul (opsional)', 200),
            Fields::select('source', 'Sumber data', ['database' => 'Dari menu Statistics', 'manual' => 'Isi manual'])->live(),
            Repeater::make('items')->label('Statistik')->schema([
                Fields::grid([Fields::text('value', 'Nilai', 20)->required(), Fields::text('suffix', 'Akhiran (+, %, dll.)', 10)]),
                Fields::text('label', 'Label', 80)->required(),
            ])->maxItems(12)->visible(fn ($get) => $get('source') === 'manual')->collapsible(),
        ];
    }

    public function prepare(array $content, array $design): array
    {
        if (($content['source'] ?? 'database') === 'manual') {
            return ['stats' => collect($content['items'] ?? [])->map(fn ($i) => (object) $i)];
        }

        return ['stats' => Statistic::query()->visible()->get()];
    }
}
