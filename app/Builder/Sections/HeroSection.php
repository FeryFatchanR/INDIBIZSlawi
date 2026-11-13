<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;
use App\Builder\Style\StyleTokens;
use Filament\Forms\Components\Textarea;

class HeroSection extends SectionType
{
    public function key(): string
    {
        return 'hero';
    }

    public function label(): string
    {
        return 'Hero';
    }

    public function category(): string
    {
        return 'Header & Banner';
    }

    public function icon(): string
    {
        return 'heroicon-o-sparkles';
    }

    public function view(): string
    {
        return 'builder.hero';
    }

    public function defaultContent(): array
    {
        return [
            'eyebrow' => 'Internet Bisnis',
            'heading' => 'Konektivitas andal untuk bisnis Anda',
            'subtitle' => 'Internet dedicated & broadband bisnis dengan SLA dan dukungan 24/7.',
            'description' => null,
            'primary_button' => ['label' => 'Konsultasi Gratis', 'url' => '/kontak', 'style' => 'accent'],
            'secondary_button' => ['label' => 'Lihat Layanan', 'url' => '/layanan', 'style' => 'outline'],
            'background_image_id' => null,
            'image_id' => null,
        ];
    }

    public function defaultDesign(): array
    {
        return ['background' => 'gradient', 'height' => 'md', 'image_position' => 'right', 'overlay' => 'brand', 'padding_y' => 'xl'];
    }

    public function contentRules(): array
    {
        return [
            'eyebrow' => ['nullable', 'string', 'max:80'],
            'heading' => ['required', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:1000'],
            'background_image_id' => $this->mediaRule(),
            'image_id' => $this->mediaRule(),
            ...$this->buttonRules('primary_button'),
            ...$this->buttonRules('secondary_button'),
        ];
    }

    public function designRules(): array
    {
        return [
            'height' => ['nullable', 'in:'.StyleTokens::keys(StyleTokens::HEIGHTS)],
            'image_position' => ['nullable', 'in:left,right,none'],
            'overlay' => ['nullable', 'in:'.StyleTokens::keys(StyleTokens::OVERLAYS)],
        ];
    }

    public function mediaFields(): array
    {
        return ['background_image_id', 'image_id'];
    }

    public function contentFields(): array
    {
        return [
            Fields::text('eyebrow', 'Label kecil (eyebrow)', 80),
            Fields::text('heading', 'Judul', 160)->required(),
            Textarea::make('subtitle')->label('Subjudul')->maxLength(300)->rows(2),
            Textarea::make('description')->label('Deskripsi')->maxLength(1000)->rows(3),
            Fields::media('background_image_id', 'Gambar latar'),
            Fields::media('image_id', 'Gambar samping'),
            Fields::button('primary_button', 'Tombol utama'),
            Fields::button('secondary_button', 'Tombol kedua'),
        ];
    }

    public function designFields(): array
    {
        return [
            Fields::select('height', 'Tinggi section', StyleTokens::HEIGHTS),
            Fields::select('image_position', 'Posisi gambar samping', ['right' => 'Kanan', 'left' => 'Kiri', 'none' => 'Sembunyikan']),
            Fields::select('overlay', 'Overlay gambar latar', StyleTokens::OVERLAYS),
        ];
    }
}
