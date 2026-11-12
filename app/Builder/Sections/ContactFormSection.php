<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;
use App\Models\IndustrySolution;
use App\Models\Product;
use Filament\Forms\Components\Textarea;

class ContactFormSection extends SectionType
{
    public function key(): string
    {
        return 'contact_form';
    }

    public function label(): string
    {
        return 'Contact Form';
    }

    public function category(): string
    {
        return 'Konversi';
    }

    public function icon(): string
    {
        return 'heroicon-o-envelope';
    }

    public function view(): string
    {
        return 'builder.contact-form';
    }

    public function defaultContent(): array
    {
        return ['heading' => 'Konsultasikan Kebutuhan Jaringan Anda', 'description' => 'Tim kami akan menghubungi Anda maksimal 1x24 jam kerja.',
            'form_type' => 'consultation', 'submit_label' => 'Kirim Permintaan', 'show_contact_info' => true, 'show_rfq_fields' => true];
    }

    public function defaultDesign(): array
    {
        return ['background' => 'surface'];
    }

    public function contentRules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            'form_type' => ['required', 'in:contact,rfq,consultation'],
            'submit_label' => ['nullable', 'string', 'max:40'],
            'show_contact_info' => ['nullable', 'boolean'],
            'show_rfq_fields' => ['nullable', 'boolean'],
        ];
    }

    public function contentFields(): array
    {
        return [
            Fields::text('heading', 'Judul', 200),
            Textarea::make('description')->label('Deskripsi')->maxLength(500)->rows(2),
            Fields::select('form_type', 'Jenis form (tercatat di lead)', ['contact' => 'Kontak umum', 'rfq' => 'Request for Quotation', 'consultation' => 'Konsultasi']),
            Fields::text('submit_label', 'Label tombol kirim', 40),
            Fields::toggle('show_rfq_fields', 'Tampilkan field kebutuhan (produk, bandwidth, jumlah lokasi)'),
            Fields::toggle('show_contact_info', 'Tampilkan info kontak di samping form'),
        ];
    }

    public function prepare(array $content, array $design): array
    {
        return [
            'products' => Product::query()->published()->get(['id', 'name']),
            'industries' => IndustrySolution::query()->visible()->get(['id', 'name']),
        ];
    }
}
