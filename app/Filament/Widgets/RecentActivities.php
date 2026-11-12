<?php

namespace App\Filament\Widgets;

use App\Models\ActivityLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentActivities extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Aktivitas terbaru';

    public static function canView(): bool
    {
        return auth()->user()->can('activity.view');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ActivityLog::query()->with('user')->latest('created_at')->limit(10))
            ->paginated(false)
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->since(),
                TextColumn::make('user.name')->label('Pengguna')->placeholder('Sistem'),
                TextColumn::make('event')->label('Event')->badge(),
                TextColumn::make('description')->label('Deskripsi')->limit(70),
            ]);
    }
}
