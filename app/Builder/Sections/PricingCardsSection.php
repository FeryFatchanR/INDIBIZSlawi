<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;
use App\Builder\Style\StyleTokens;
use App\Rules\SafeUrl;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;

class PricingCardsSection extends SectionType
{
    public function key(): string
    {
        return 'pricing_cards';
    }

    public function label(): string
    {
        return 'Pricing Cards';
    }

    public function category(): string
    {
        return 'Produk & Penjualan';
    }

    public function icon(): string
    {
        return 'heroicon-o-currency-dollar';
    }

    public function view(): string
    {
        return 'builder.pricing-cards';
    }

    public function supportsColumns(): bool
    {
        return true;
    }

    public function defaultContent(): array
    {
        return ['heading' => 'Pilihan Paket', 'subheading' => null, 'note' => 'Harga belum termasuk PPN. Hubungi kami untuk penawaran khusus.', 'plans' => [
            ['name' => 'Paket Basic', 'price' => 'Hubungi Kami', 'period' => null, 'bandwidth' => '50 Mbps', 'features' => "Dedicated bandwidth\nSupport 24/7", 'highlighted' => false, 'button_label' => 'Minta Penawaran', 'button_url' => '/kontak'],
        ]];
    }

    public function defaultDesign(): array
    {
        return ['text_align' => 'center', 'card_style' => 'elevated'];
    }

    public function contentRules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:200'],
            'subheading' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
            'plans' => ['nullable', 'array', 'max:8'],
            'plans.*.name' => ['required', 'string', 'max:80'],
            'plans.*.price' => ['nullable', 'string', 'max:60'],
            'plans.*.period' => ['nullable', 'string', 'max:30'],
            'plans.*.bandwidth' => ['nullable', 'string', 'max:60'],
            'plans.*.features' => ['nullable', 'string', 'max:2000'],
            'plans.*.highlighted' => ['nullable', 'boolean'],
            'plans.*.button_label' => ['nullable', 'string', 'max:40'],
            'plans.*.button_url' => ['nullable', 'string', 'max:2048', new SafeUrl],
        ];
    }

    public function designRules(): array
    {
        return ['card_style' => ['nullable', 'in:'.StyleTokens::keys(StyleTokens::CARD_STYLES)]];
    }

    public function contentFields(): array
    {
        return [
            Fields::text('heading', 'Judul', 200),
            Textarea::make('subheading')->label('Subjudul')->maxLength(500)->rows(2),
            Repeater::make('plans')->label('Paket')->schema([
                Fields::text('name', 'Nama paket', 80)->required(),
                Fields::grid([Fields::text('price', 'Harga', 60), Fields::text('period', 'Periode (mis. /bulan)', 30)]),
                Fields::text('bandwidth', 'Bandwidth', 60),
                Textarea::make('features')->label('Fitur (satu per baris)')->rows(4)->maxLength(2000),
                Fields::toggle('highlighted', 'Tandai sebagai rekomendasi'),
                Fields::grid([Fields::text('button_label', 'Label tombol', 40), Fields::url('button_url', 'URL tombol')]),
            ])->maxItems(8)->collapsible()->reorderableWithButtons()->itemLabel(fn (array $state) => $state['name'] ?? null),
            Textarea::make('note')->label('Catatan bawah')->maxLength(500)->rows(2),
        ];
    }

    public function designFields(): array
    {
        return [Fields::select('card_style', 'Gaya kartu', StyleTokens::CARD_STYLES)];
    }
}
