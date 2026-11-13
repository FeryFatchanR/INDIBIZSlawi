<?php

namespace App\Filament\Support;

use App\Builder\Fields;
use App\Rules\SafeUrl;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

/** Field form admin yang dipakai ulang lintas resource. */
final class FormFields
{
    /** Field judul yang otomatis mengisi slug saat membuat data baru. */
    public static function titleWithSlug(string $name = 'name', string $label = 'Nama', int $max = 150): TextInput
    {
        return TextInput::make($name)->label($label)->required()->maxLength($max)
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, ?string $state, string $operation) {
                if ($operation === 'create') {
                    $set('slug', Str::slug((string) $state));
                }
            });
    }

    public static function slug(string $table, int $max = 160, array $reserved = []): TextInput
    {
        return TextInput::make('slug')->label('Slug (URL)')->required()->maxLength($max)
            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
            ->unique($table, 'slug', ignoreRecord: true)
            ->notIn($reserved)
            ->validationMessages(['regex' => 'Slug hanya huruf kecil, angka, dan tanda hubung.', 'not_in' => 'Slug ini dipakai sistem.'])
            ->helperText('Huruf kecil, angka, dan tanda hubung. Contoh: dedicated-internet');
    }

    public static function url(string $name, string $label = 'URL'): TextInput
    {
        return TextInput::make($name)->label($label)->maxLength(2048)->rule(new SafeUrl)
            ->placeholder('/kontak atau https://...');
    }

    public static function media(string $name, string $label = 'Gambar', string $kind = 'image')
    {
        return Fields::media($name, $label, $kind);
    }

    public static function visible(): Toggle
    {
        return Toggle::make('is_visible')->label('Tampilkan di website')->default(true);
    }

    public static function seoFields(): array
    {
        return [
            TextInput::make('meta_title')->label('Meta title')->maxLength(70)->helperText('Kosongkan untuk memakai judul. Ideal ≤ 60 karakter.'),
            Textarea::make('meta_description')->label('Meta description')->maxLength(320)->rows(2)
                ->helperText('Ringkasan untuk mesin pencari, ideal 120–160 karakter.'),
        ];
    }
}
