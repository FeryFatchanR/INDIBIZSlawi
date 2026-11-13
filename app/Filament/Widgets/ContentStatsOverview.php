<?php

namespace App\Filament\Widgets;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Page;
use App\Models\Product;
use App\Support\Access;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return Access::can('pages.view') || Access::can('products.view');
    }

    protected function getStats(): array
    {
        $pages = Page::query()->where('type', '!=', PageType::Global)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            Stat::make('Total produk', (string) Product::query()->count())->icon('heroicon-o-rectangle-stack'),
            Stat::make('Halaman published', (string) ($pages[PageStatus::Published->value] ?? 0))->color('success')->icon('heroicon-o-globe-alt'),
            Stat::make('Halaman draft', (string) (($pages[PageStatus::Draft->value] ?? 0) + ($pages[PageStatus::Unpublished->value] ?? 0)))->color('gray')->icon('heroicon-o-pencil-square'),
        ];
    }
}
