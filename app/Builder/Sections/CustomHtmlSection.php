<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use Filament\Forms\Components\Textarea;

/**
 * HTML kustom — hanya untuk pemegang permission `builder.custom_html`. Tetap disanitasi ketat
 * (tanpa script, iframe, style, event handler) saat simpan DAN saat render.
 */
class CustomHtmlSection extends SectionType
{
    public function key(): string
    {
        return 'custom_html';
    }

    public function label(): string
    {
        return 'Custom HTML';
    }

    public function category(): string
    {
        return 'Lanjutan';
    }

    public function icon(): string
    {
        return 'heroicon-o-code-bracket';
    }

    public function view(): string
    {
        return 'builder.custom-html';
    }

    public function permission(): ?string
    {
        return 'builder.custom_html';
    }

    public function defaultContent(): array
    {
        return ['html' => '<div class="text-center"><p>Konten HTML kustom</p></div>'];
    }

    public function contentRules(): array
    {
        return ['html' => ['nullable', 'string', 'max:50000']];
    }

    public function richTextFields(): array
    {
        return ['html'];
    }

    public function contentFields(): array
    {
        return [
            Textarea::make('html')->label('HTML')->rows(12)->maxLength(50000)
                ->helperText('Script, iframe, style inline, dan event handler akan dihapus otomatis.'),
        ];
    }
}
