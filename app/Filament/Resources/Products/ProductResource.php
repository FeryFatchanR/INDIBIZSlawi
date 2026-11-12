<?php

namespace App\Filament\Resources\Products;

use App\Builder\Style\Icons;
use App\Enums\ProductStatus;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\FaqsRelationManager;
use App\Filament\Resources\Products\RelationManagers\GalleriesRelationManager;
use App\Filament\Resources\Products\RelationManagers\SpecificationsRelationManager;
use App\Filament\Support\FormFields;
use App\Models\Product;
use App\Support\MediaUsageTracker;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Produk';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Produk';

    protected static ?string $pluralModelLabel = 'Products';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Tabs::make('Produk')->persistTabInQueryString()->tabs([
                Tab::make('Informasi')->icon('heroicon-o-information-circle')->columns(2)->schema([
                    FormFields::titleWithSlug('name', 'Nama produk', 150),
                    FormFields::slug('products'),
                    Select::make('icon')->label('Ikon')->options(Icons::OPTIONS)->searchable(),
                    Select::make('categories')->label('Kategori')->relationship('categories', 'name')->multiple()->preload(),
                    Textarea::make('short_description')->label('Deskripsi singkat')->maxLength(500)->rows(2)->columnSpanFull(),
                    RichEditor::make('description')->label('Deskripsi lengkap')->columnSpanFull()
                        ->toolbarButtons([['bold', 'italic', 'underline', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']]),
                ]),
                Tab::make('Detail Layanan')->icon('heroicon-o-signal')->columns(2)->schema([
                    TextInput::make('bandwidth_label')->label('Bandwidth')->maxLength(120)->placeholder('mis. 10 Mbps – 10 Gbps'),
                    TextInput::make('sla')->label('SLA')->maxLength(120),
                    TagsInput::make('benefits')->label('Manfaat')->placeholder('Ketik lalu Enter')->columnSpanFull()
                        ->nestedRecursiveRules(['string', 'max:200']),
                    TagsInput::make('target_customers')->label('Target pelanggan')->placeholder('Ketik lalu Enter')->columnSpanFull()
                        ->nestedRecursiveRules(['string', 'max:100']),
                    TextInput::make('price_label')->label('Label harga')->maxLength(120)->placeholder('mis. Mulai Rp xxx/bulan'),
                    Toggle::make('show_price')->label('Tampilkan harga di website'),
                    TextInput::make('cta_label')->label('Label tombol CTA')->maxLength(80),
                    FormFields::url('cta_url', 'URL tombol CTA'),
                ]),
                Tab::make('Media')->icon('heroicon-o-photo')->schema([
                    FormFields::media('featured_image_id', 'Gambar utama'),
                    FormFields::media('og_image_id', 'Gambar Open Graph (share sosial media)'),
                ]),
                Tab::make('SEO & Publikasi')->icon('heroicon-o-globe-alt')->columns(2)->schema([
                    ...FormFields::seoFields(),
                    Select::make('status')->label('Status')->options(ProductStatus::options())->default(ProductStatus::Draft->value)->required()
                        ->disabled(fn () => ! auth()->user()?->can('products.update')),
                    DateTimePicker::make('published_at')->label('Tanggal publikasi')->helperText('Kosongkan untuk langsung tayang saat status Published.'),
                    Toggle::make('is_featured')->label('Produk unggulan'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('categories'))
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('categories.name')->label('Kategori')->badge(),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (ProductStatus $state) => $state->label())
                    ->color(fn (ProductStatus $state) => $state === ProductStatus::Published ? 'success' : 'gray'),
                ToggleColumn::make('is_featured')->label('Unggulan'),
                TextColumn::make('updated_at')->label('Diubah')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ProductStatus::options()),
                SelectFilter::make('categories')->label('Kategori')->relationship('categories', 'name'),
                TrashedFilter::make(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->recordActions([
                Action::make('view_public')->label('Lihat')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Product $record) => $record->publicUrl(), shouldOpenInNewTab: true)
                    ->visible(fn (Product $record) => $record->isPublished()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make(), RestoreBulkAction::make(), ForceDeleteBulkAction::make()]),
            ]);
    }

    public static function getRelations(): array
    {
        return [SpecificationsRelationManager::class, GalleriesRelationManager::class, FaqsRelationManager::class];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function syncMediaUsage(Product $product): void
    {
        MediaUsageTracker::sync($product, [
            'featured_image_id' => $product->featured_image_id,
            'og_image_id' => $product->og_image_id,
            'gallery' => $product->galleries()->pluck('media_asset_id')->all(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
