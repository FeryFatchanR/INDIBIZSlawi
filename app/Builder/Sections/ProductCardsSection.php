<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;
use App\Builder\Style\StyleTokens;
use App\Models\Product;
use App\Models\ProductCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class ProductCardsSection extends SectionType
{
    public function key(): string
    {
        return 'product_cards';
    }

    public function label(): string
    {
        return 'Product Cards';
    }

    public function category(): string
    {
        return 'Produk & Penjualan';
    }

    public function icon(): string
    {
        return 'heroicon-o-rectangle-stack';
    }

    public function view(): string
    {
        return 'builder.product-cards';
    }

    public function supportsColumns(): bool
    {
        return true;
    }

    public function defaultContent(): array
    {
        return ['heading' => 'Layanan Kami', 'subheading' => null, 'source' => 'all', 'category_id' => null,
            'product_ids' => [], 'limit' => 6, 'button_label' => 'Lihat Detail', 'button_target' => 'product_page'];
    }

    public function defaultDesign(): array
    {
        return ['text_align' => 'center', 'card_style' => 'elevated', 'show_image' => true, 'show_price' => false, 'background' => 'surface'];
    }

    public function contentRules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:200'],
            'subheading' => ['nullable', 'string', 'max:500'],
            'source' => ['required', 'in:all,featured,category,manual'],
            'category_id' => ['nullable', 'required_if:source,category', 'integer', 'exists:product_categories,id'],
            'product_ids' => ['nullable', 'array', 'max:24'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'limit' => ['nullable', 'integer', 'between:1,24'],
            'button_label' => ['nullable', 'string', 'max:40'],
            'button_target' => ['nullable', 'in:product_page,whatsapp,contact'],
        ];
    }

    public function designRules(): array
    {
        return [
            'card_style' => ['nullable', 'in:'.StyleTokens::keys(StyleTokens::CARD_STYLES)],
            'show_image' => ['nullable', 'boolean'],
            'show_price' => ['nullable', 'boolean'],
        ];
    }

    public function contentFields(): array
    {
        return [
            Fields::text('heading', 'Judul', 200),
            Textarea::make('subheading')->label('Subjudul')->maxLength(500)->rows(2),
            Fields::select('source', 'Sumber produk', ['all' => 'Semua produk published', 'featured' => 'Produk unggulan', 'category' => 'Berdasarkan kategori', 'manual' => 'Pilih manual'])->live(),
            Select::make('category_id')->label('Kategori')->options(fn () => ProductCategory::pluck('name', 'id'))
                ->visible(fn ($get) => $get('source') === 'category'),
            Select::make('product_ids')->label('Produk')->multiple()->options(fn () => Product::orderBy('name')->pluck('name', 'id'))
                ->visible(fn ($get) => $get('source') === 'manual'),
            TextInput::make('limit')->label('Jumlah maksimal')->numeric()->minValue(1)->maxValue(24),
            Fields::text('button_label', 'Label tombol kartu', 40),
            Fields::select('button_target', 'Tujuan tombol', ['product_page' => 'Halaman detail produk', 'whatsapp' => 'WhatsApp', 'contact' => 'Halaman kontak']),
        ];
    }

    public function designFields(): array
    {
        return [
            Fields::select('card_style', 'Gaya kartu', StyleTokens::CARD_STYLES),
            Fields::toggle('show_image', 'Tampilkan gambar'),
            Fields::toggle('show_price', 'Tampilkan harga'),
        ];
    }

    public function prepare(array $content, array $design): array
    {
        $query = Product::query()->published()->with('featuredImage');

        match ($content['source'] ?? 'all') {
            'featured' => $query->where('is_featured', true),
            'category' => $query->whereHas('categories', fn ($q) => $q->whereKey($content['category_id'] ?? 0)),
            'manual' => $query->whereIn('id', $content['product_ids'] ?? []),
            default => null,
        };

        $products = $query->limit((int) ($content['limit'] ?? 6))->get();

        if (($content['source'] ?? null) === 'manual') {
            $order = array_flip($content['product_ids'] ?? []);
            $products = $products->sortBy(fn ($p) => $order[$p->id] ?? PHP_INT_MAX)->values();
        }

        return ['products' => $products];
    }
}
