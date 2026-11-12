<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LeadStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()->can('viewAny', Lead::class);
    }

    protected function getStats(): array
    {
        // Satu query agregat, sudah ter-scope sesuai hak akses user.
        $counts = Lead::query()->visibleTo(auth()->user())
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $total = (int) $counts->sum();
        $inProgress = collect(LeadStatus::inProgress())->sum(fn ($s) => (int) ($counts[$s->value] ?? 0));
        $won = (int) ($counts[LeadStatus::Won->value] ?? 0);
        $closed = $won + (int) ($counts[LeadStatus::Lost->value] ?? 0);

        return [
            Stat::make('Total leads', number_format($total, 0, ',', '.'))->icon('heroicon-o-inbox-stack'),
            Stat::make('New leads', (string) ($counts[LeadStatus::New->value] ?? 0))->color('info')->icon('heroicon-o-sparkles'),
            Stat::make('In progress', (string) $inProgress)->color('warning')->icon('heroicon-o-arrow-path'),
            Stat::make('Converted (won)', (string) $won)->color('success')->icon('heroicon-o-trophy')
                ->description($closed > 0 ? 'Win rate '.round($won / $closed * 100).'% dari lead closed' : 'Belum ada lead closed'),
        ];
    }
}
