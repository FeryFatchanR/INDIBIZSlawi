<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;
use Filament\Forms\Components\Textarea;

class CtaSection extends SectionType
{
    public function key(): string
    {
        return 'cta';
    }

    public function label(): string
    {
        return 'CTA';
    }

    public function category(): string
    {
        return 'Konversi';
    }

    public function icon(): string
    {
        return 'heroicon-o-cursor-arrow-rays';
    }

    public function view(): string
    {
        return 'builder.cta';
    }

    public function defaultContent(): array
    {
        return ['heading' => 'Siap meningkatkan konektivitas bisnis Anda?', 'description' => 'Dapatkan konsultasi dan penawaran gratis dari tim kami.',
            'primary_button' => ['label' => 'Minta Penawaran', 'url' => '/kontak', 'style' => 'accent'],
            'show_whatsapp' => true, 'whatsapp_label' => 'Chat WhatsApp'];
    }

    public function defaultDesign(): array
    {
        return ['background' => 'gradient', 'text_align' => 'center'];
    }

    public function contentRules(): array
    {
        return [
            'heading' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            ...$this->buttonRules('primary_button'),
            'show_whatsapp' => ['nullable', 'boolean'],
            'whatsapp_label' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function contentFields(): array
    {
        return [
            Fields::text('heading', 'Judul', 200)->required(),
            Textarea::make('description')->label('Deskripsi')->maxLength(500)->rows(2),
            Fields::button('primary_button', 'Tombol utama'),
            Fields::toggle('show_whatsapp', 'Tampilkan tombol WhatsApp')->live(),
            Fields::text('whatsapp_label', 'Label tombol WhatsApp', 40)->visible(fn ($get) => (bool) $get('show_whatsapp')),
        ];
    }
}
