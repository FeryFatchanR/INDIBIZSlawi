<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;
use App\Models\Testimonial;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class TestimonialsSection extends SectionType
{
    public function key(): string
    {
        return 'testimonials';
    }

    public function label(): string
    {
        return 'Testimonials';
    }

    public function category(): string
    {
        return 'Kepercayaan';
    }

    public function icon(): string
    {
        return 'heroicon-o-chat-bubble-bottom-center-text';
    }

    public function view(): string
    {
        return 'builder.testimonials';
    }

    public function supportsColumns(): bool
    {
        return true;
    }

    public function defaultContent(): array
    {
        return ['heading' => 'Apa Kata Pelanggan Kami', 'subheading' => null, 'limit' => 6];
    }

    public function defaultDesign(): array
    {
        return ['text_align' => 'center', 'background' => 'surface', 'show_rating' => true];
    }

    public function contentRules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:200'],
            'subheading' => ['nullable', 'string', 'max:500'],
            'limit' => ['nullable', 'integer', 'between:1,24'],
        ];
    }

    public function designRules(): array
    {
        return ['show_rating' => ['nullable', 'boolean']];
    }

    public function contentFields(): array
    {
        return [
            Fields::text('heading', 'Judul', 200),
            Textarea::make('subheading')->label('Subjudul')->maxLength(500)->rows(2),
            TextInput::make('limit')->label('Jumlah testimoni')->numeric()->minValue(1)->maxValue(24)
                ->helperText('Testimoni dikelola di menu Marketing → Testimonials.'),
        ];
    }

    public function designFields(): array
    {
        return [Fields::toggle('show_rating', 'Tampilkan rating')];
    }

    public function prepare(array $content, array $design): array
    {
        return ['testimonials' => Testimonial::query()->visible()->with('photo')->limit((int) ($content['limit'] ?? 6))->get()];
    }
}
