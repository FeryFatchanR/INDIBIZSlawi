<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Support\CmsCache;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

/**
 * Invalidasi cache konten publik. Sengaja TIDAK menjalankan perintah artisan dari web;
 * hanya menaikkan versi cache CMS sehingga cache lama tidak dipakai lagi.
 */
class CacheManagement extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $title = 'Cache Management';

    protected string $view = 'filament.pages.cache-management';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('cache.manage') ?? false;
    }

    public function getCacheVersion(): int
    {
        return CmsCache::version();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('flush')->label('Bersihkan cache konten')->icon('heroicon-o-arrow-path')->requiresConfirmation()
                ->action(function () {
                    abort_unless(static::canAccess(), 403);
                    CmsCache::bump();
                    ActivityLog::record('cache.flushed', null, 'Cache konten publik dibersihkan');
                    Notification::make()->title('Cache konten dibersihkan.')->success()->send();
                }),
        ];
    }
}
