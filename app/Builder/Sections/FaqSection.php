<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;
use App\Models\Faq;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;

class FaqSection extends SectionType
{
    public function key(): string
    {
        return 'faq';
    }

    public function label(): string
    {
        return 'FAQ';
    }

    public function category(): string
    {
        return 'Konten';
    }

    public function icon(): string
    {
        return 'heroicon-o-question-mark-circle';
    }

    public function view(): string
    {
        return 'builder.faq';
    }

    public function defaultContent(): array
    {
        return ['heading' => 'Pertanyaan yang Sering Diajukan', 'subheading' => null, 'source' => 'database', 'group' => 'general', 'items' => []];
    }

    public function defaultDesign(): array
    {
        return ['container' => 'narrow', 'text_align' => 'center'];
    }

    public function contentRules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:200'],
            'subheading' => ['nullable', 'string', 'max:500'],
            'source' => ['required', 'in:database,manual'],
            'group' => ['nullable', 'string', 'max:60', 'alpha_dash'],
            'items' => ['nullable', 'array', 'max:40'],
            'items.*.question' => ['required', 'string', 'max:255'],
            'items.*.answer' => ['required', 'string', 'max:3000'],
        ];
    }

    public function contentFields(): array
    {
        return [
            Fields::text('heading', 'Judul', 200),
            Textarea::make('subheading')->label('Subjudul')->maxLength(500)->rows(2),
            Fields::select('source', 'Sumber FAQ', ['database' => 'Dari menu FAQ (per grup)', 'manual' => 'Isi manual'])->live(),
            Fields::text('group', 'Grup FAQ', 60)->visible(fn ($get) => $get('source') === 'database'),
            Repeater::make('items')->label('Pertanyaan')->schema([
                Fields::text('question', 'Pertanyaan', 255)->required(),
                Textarea::make('answer')->label('Jawaban')->required()->maxLength(3000)->rows(3),
            ])->maxItems(40)->visible(fn ($get) => $get('source') === 'manual')->collapsible()
                ->itemLabel(fn (array $state) => $state['question'] ?? null),
        ];
    }

    public function prepare(array $content, array $design): array
    {
        if (($content['source'] ?? 'database') === 'manual') {
            return ['faqs' => collect($content['items'] ?? [])->map(fn ($i) => (object) $i)];
        }

        return ['faqs' => Faq::query()->visible()->where('group', $content['group'] ?? 'general')->get()];
    }
}
