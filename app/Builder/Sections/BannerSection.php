<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;
use App\Models\Banner;
use Filament\Forms\Components\Textarea;

class BannerSection extends SectionType
{
    public function key(): string
    {
        return 'banner';
    }

    public function label(): string
    {
        return 'Banner';
    }

    public function category(): string
    {
        return 'Header & Banner';
    }

    public function icon(): string
    {
        return 'heroicon-o-megaphone';
    }

    public function view(): string
    {
        return 'builder.banner';
    }

    public function defaultContent(): array
    {
        return ['source' => 'manual', 'placement' => 'home', 'title' => 'Promo Internet Bisnis', 'subtitle' => null, 'image_id' => null,
            'button' => ['label' => 'Hubungi Kami', 'url' => '/kontak', 'style' => 'light']];
    }

    public function defaultDesign(): array
    {
        return ['background' => 'secondary', 'padding_y' => 'md'];
    }

    public function contentRules(): array
    {
        return [
            'source' => ['required', 'in:manual,banners'],
            'placement' => ['nullable', 'string', 'max:40', 'alpha_dash'],
            'title' => ['nullable', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:300'],
            'image_id' => $this->mediaRule(),
            ...$this->buttonRules('button'),
        ];
    }

    public function mediaFields(): array
    {
        return ['image_id'];
    }

    public function contentFields(): array
    {
        return [
            Fields::select('source', 'Sumber konten', ['manual' => 'Isi manual', 'banners' => 'Dari menu Banners (aktif)'])->live(),
            Fields::text('placement', 'Placement banner', 40)->visible(fn ($get) => $get('source') === 'banners'),
            Fields::text('title', 'Judul', 160)->visible(fn ($get) => $get('source') !== 'banners'),
            Textarea::make('subtitle')->label('Subjudul')->maxLength(300)->visible(fn ($get) => $get('source') !== 'banners'),
            Fields::media('image_id', 'Gambar')->visible(fn ($get) => $get('source') !== 'banners'),
            Fields::button('button', 'Tombol')->visible(fn ($get) => $get('source') !== 'banners'),
        ];
    }

    public function prepare(array $content, array $design): array
    {
        if (($content['source'] ?? 'manual') !== 'banners') {
            return ['banners' => collect()];
        }

        return ['banners' => Banner::query()->visible()->running()
            ->where('placement', $content['placement'] ?? 'home')->with('image')->limit(5)->get()];
    }
}
