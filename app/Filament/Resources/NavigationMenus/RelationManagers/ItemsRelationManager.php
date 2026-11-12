<?php

namespace App\Filament\Resources\NavigationMenus\RelationManagers;

use App\Enums\PageType;
use App\Filament\Support\FormFields;
use App\Models\NavigationMenuItem;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Item Menu';

    public function form(Schema $schema): Schema
    {
        $menuId = $this->getOwnerRecord()->getKey();

        return $schema->columns(2)->components([
            TextInput::make('label')->label('Label')->required()->maxLength(120),
            Select::make('parent_id')->label('Induk (submenu)')
                ->options(fn (?NavigationMenuItem $record) => NavigationMenuItem::query()->where('navigation_menu_id', $menuId)
                    ->whereNull('parent_id')->when($record, fn ($query) => $query->whereKeyNot($record->id))->pluck('label', 'id'))
                ->rule(Rule::exists('navigation_menu_items', 'id')->where('navigation_menu_id', $menuId)->whereNull('parent_id'))
                ->placeholder('— Menu utama —'),
            Select::make('link_type')->label('Jenis tautan')->required()->default('url')->live()
                ->options(['url' => 'URL', 'page' => 'Halaman', 'product' => 'Produk', 'solution' => 'Solusi industri']),
            FormFields::url('url', 'URL')->visible(fn ($get) => $get('link_type') === 'url')->required(fn ($get) => $get('link_type') === 'url'),
            Select::make('page_id')->label('Halaman')->relationship('page', 'title', fn ($query) => $query->where('type', '!=', PageType::Global))
                ->searchable()->preload()->visible(fn ($get) => $get('link_type') === 'page')->required(fn ($get) => $get('link_type') === 'page'),
            Select::make('product_id')->label('Produk')->relationship('product', 'name')->searchable()->preload()
                ->visible(fn ($get) => $get('link_type') === 'product')->required(fn ($get) => $get('link_type') === 'product'),
            Select::make('industry_solution_id')->label('Solusi industri')->relationship('industrySolution', 'name')->searchable()->preload()
                ->visible(fn ($get) => $get('link_type') === 'solution')->required(fn ($get) => $get('link_type') === 'solution'),
            Toggle::make('open_in_new_tab')->label('Buka di tab baru'),
            Toggle::make('is_visible')->label('Tampil')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['parent', 'page', 'product', 'industrySolution']))
            ->columns([
                TextColumn::make('label')->label('Label')
                    ->formatStateUsing(fn (NavigationMenuItem $r, $state) => $r->parent_id ? '↳ '.$state : $state),
                TextColumn::make('parent.label')->label('Induk'),
                TextColumn::make('link_type')->label('Jenis')->badge(),
                TextColumn::make('target')->label('Tujuan')->state(fn (NavigationMenuItem $r) => $r->resolvedUrl() ?? '(target belum publish)')->limit(40),
                ToggleColumn::make('is_visible')->label('Tampil'),
            ])
            ->reorderable('sort_order')->defaultSort('sort_order')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
