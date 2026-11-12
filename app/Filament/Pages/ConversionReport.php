<?php

namespace App\Filament\Pages;

use App\Enums\LeadStatus;
use App\Models\Lead;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/** Laporan konversi lead per bulan, sumber, dan produk (agregasi di database). */
class ConversionReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string|UnitEnum|null $navigationGroup = 'Leads';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Conversion Report';

    protected string $view = 'filament.pages.conversion-report';

    public int $months = 6;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('leads.reports') ?? false;
    }

    protected function base()
    {
        return Lead::query()->where('leads.created_at', '>=', now()->subMonths($this->months)->startOfMonth());
    }

    public function byMonth(): array
    {
        $driver = DB::connection()->getDriverName();
        $expr = $driver === 'sqlite' ? "strftime('%Y-%m', leads.created_at)" : "DATE_FORMAT(leads.created_at, '%Y-%m')";

        return $this->base()
            ->selectRaw("{$expr} as period, COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as won, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as lost",
                [LeadStatus::Won->value, LeadStatus::Lost->value])
            ->groupBy('period')->orderBy('period')->get()->map(fn ($r) => (array) $r->getAttributes())->all();
    }

    public function bySource(): array
    {
        return $this->base()
            ->selectRaw('leads.source as source, COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as won', [LeadStatus::Won->value])
            ->groupBy('leads.source')->orderByDesc('total')->get()->map(fn ($r) => (array) $r->getAttributes())->all();
    }

    public function byProduct(): array
    {
        return $this->base()->leftJoin('products', 'products.id', '=', 'leads.product_id')
            ->selectRaw("COALESCE(products.name, '(tidak dipilih)') as product, COUNT(*) as total, SUM(CASE WHEN leads.status = ? THEN 1 ELSE 0 END) as won", [LeadStatus::Won->value])
            ->groupBy('product')->orderByDesc('total')->get()->map(fn ($r) => (array) $r->getAttributes())->all();
    }

    public static function rate(int|string $won, int|string $total): string
    {
        return (int) $total > 0 ? round((int) $won / (int) $total * 100, 1).'%' : '-';
    }
}
