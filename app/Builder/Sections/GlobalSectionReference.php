<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Enums\PageType;
use App\Models\Page;
use Filament\Forms\Components\Select;

/** Menyisipkan Global Section (halaman bertipe "global") yang dapat dipakai ulang di banyak halaman. */
class GlobalSectionReference extends SectionType
{
    public function key(): string
    {
        return 'global_section';
    }

    public function label(): string
    {
        return 'Global Section';
    }

    public function category(): string
    {
        return 'Layout';
    }

    public function icon(): string
    {
        return 'heroicon-o-globe-alt';
    }

    public function view(): string
    {
        return 'builder.global-section';
    }

    public function defaultContent(): array
    {
        return ['page_id' => null];
    }

    public function defaultDesign(): array
    {
        return ['padding_y' => 'none'];
    }

    public function contentRules(): array
    {
        return ['page_id' => ['required', 'integer', 'exists:pages,id']];
    }

    public function contentFields(): array
    {
        return [
            Select::make('page_id')->label('Global section')->required()
                ->options(fn () => Page::query()->where('type', PageType::Global)->pluck('title', 'id'))
                ->helperText('Hanya versi yang sudah dipublikasikan yang tampil.'),
        ];
    }
}
