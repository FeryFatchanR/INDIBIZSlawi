<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Filament\Widgets\ChartWidget;

class LeadsByStatusChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Pipeline lead';

    public static function canView(): bool
    {
        return auth()->user()->can('viewAny', Lead::class);
    }

    protected function getData(): array
    {
        $counts = Lead::query()->visibleTo(auth()->user())->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'datasets' => [[
                'label' => 'Lead',
                'data' => collect(LeadStatus::cases())->map(fn ($s) => (int) ($counts[$s->value] ?? 0))->all(),
                'backgroundColor' => ['#00B8D9', '#1769AA', '#3b82f6', '#f59e0b', '#fb923c', '#16a34a', '#dc2626'],
            ]],
            'labels' => collect(LeadStatus::cases())->map->label()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
