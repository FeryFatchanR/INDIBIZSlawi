<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;
use App\Builder\Style\Icons;
use App\Builder\Style\StyleTokens;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;

class FeaturesSection extends SectionType
{
    public function key(): string
    {
        return 'features';
    }

    public function label(): string
    {
        return 'Features';
    }

    public function category(): string
    {
        return 'Konten';
    }

    public function icon(): string
    {
        return 'heroicon-o-check-badge';
    }

    public function view(): string
    {
        return 'builder.features';
    }

    public function supportsColumns(): bool
    {
        return true;
    }

    public function defaultContent(): array
    {
        return ['eyebrow' => null, 'heading' => 'Mengapa Memilih Kami', 'subheading' => null, 'items' => [
            ['icon' => 'bolt', 'title' => 'Koneksi Stabil', 'description' => 'Jaringan fiber optik dengan uptime tinggi.'],
            ['icon' => 'lifebuoy', 'title' => 'Support 24/7', 'description' => 'Tim teknis siap membantu kapan pun.'],
            ['icon' => 'shield-check', 'title' => 'SLA Terjamin', 'description' => 'Jaminan layanan tertulis untuk bisnis.'],
        ]];
    }

    public function defaultDesign(): array
    {
        return ['text_align' => 'center', 'card_style' => 'bordered', 'layout' => 'cards'];
    }

    public function contentRules(): array
    {
        return [
            'eyebrow' => ['nullable', 'string', 'max:80'],
            'heading' => ['nullable', 'string', 'max:200'],
            'subheading' => ['nullable', 'string', 'max:500'],
            'items' => ['nullable', 'array', 'max:24'],
            'items.*.icon' => ['nullable', 'in:'.implode(',', array_keys(Icons::OPTIONS))],
            'items.*.title' => ['required', 'string', 'max:120'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function designRules(): array
    {
        return [
            'card_style' => ['nullable', 'in:'.StyleTokens::keys(StyleTokens::CARD_STYLES)],
            'layout' => ['nullable', 'in:cards,list,minimal'],
        ];
    }

    public function contentFields(): array
    {
        return [
            Fields::text('eyebrow', 'Label kecil', 80),
            Fields::text('heading', 'Judul', 200),
            Textarea::make('subheading')->label('Subjudul')->maxLength(500)->rows(2),
            Repeater::make('items')->label('Item')->schema([
                Fields::icon(),
                Fields::text('title', 'Judul', 120)->required(),
                Textarea::make('description')->label('Deskripsi')->maxLength(500)->rows(2),
            ])->maxItems(24)->collapsible()->reorderableWithButtons()->itemLabel(fn (array $state) => $state['title'] ?? null),
        ];
    }

    public function designFields(): array
    {
        return [
            Fields::select('layout', 'Tata letak', ['cards' => 'Kartu', 'list' => 'Daftar ikon', 'minimal' => 'Minimal']),
            Fields::select('card_style', 'Gaya kartu', StyleTokens::CARD_STYLES),
        ];
    }
}
