<?php

namespace App\Builder;

use App\Builder\Style\Icons;
use App\Builder\Style\StyleTokens;
use App\Models\MediaAsset;
use App\Rules\SafeUrl;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;

/** Pabrik field property editor yang konsisten untuk semua section. */
final class Fields
{
    public static function text(string $name, string $label, int $max = 255): TextInput
    {
        return TextInput::make($name)->label($label)->maxLength($max);
    }

    public static function url(string $name, string $label = 'URL'): TextInput
    {
        return TextInput::make($name)->label($label)->maxLength(2048)->rule(new SafeUrl)
            ->placeholder('/kontak atau https://...')
            ->helperText('Hanya http(s), mailto:, tel:, atau path diawali "/".');
    }

    public static function richText(string $name, string $label = 'Konten'): RichEditor
    {
        return RichEditor::make($name)->label($label)
            ->toolbarButtons([['bold', 'italic', 'underline', 'strike', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']]);
    }

    public static function media(string $name, string $label = 'Gambar', string $kind = 'image'): Select
    {
        return Select::make($name)->label($label)
            ->searchable()
            ->allowHtml()
            ->getSearchResultsUsing(fn (string $search) => self::mediaOptions($kind, $search))
            ->getOptionLabelUsing(fn ($value) => self::mediaLabel(MediaAsset::find($value)))
            ->options(fn () => self::mediaOptions($kind))
            ->helperText('Pilih dari Media Library. Upload file baru di menu Media Library.');
    }

    public static function icon(string $name = 'icon', string $label = 'Ikon'): Select
    {
        return Select::make($name)->label($label)->options(Icons::OPTIONS)->searchable();
    }

    public static function select(string $name, string $label, array $options): Select
    {
        return Select::make($name)->label($label)->options($options)->native(false);
    }

    public static function toggle(string $name, string $label): Toggle
    {
        return Toggle::make($name)->label($label);
    }

    public static function button(string $prefix, string $label): Fieldset
    {
        return Fieldset::make($label)->schema([
            TextInput::make("{$prefix}.label")->label('Teks tombol')->maxLength(60),
            self::url("{$prefix}.url", 'Tujuan tombol'),
            Select::make("{$prefix}.style")->label('Gaya tombol')->options(StyleTokens::BUTTON_STYLES),
            Toggle::make("{$prefix}.new_tab")->label('Buka di tab baru'),
        ])->columns(1);
    }

    public static function grid(array $fields, int $columns = 2): Grid
    {
        return Grid::make($columns)->schema($fields);
    }

    /** @return array<int|string, string> */
    private static function mediaOptions(string $kind, string $search = ''): array
    {
        return MediaAsset::query()
            ->where('kind', $kind)
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('original_name', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%")))
            ->latest()->limit(50)->get()
            ->mapWithKeys(fn (MediaAsset $m) => [$m->id => self::mediaLabel($m)])
            ->all();
    }

    private static function mediaLabel(?MediaAsset $media): string
    {
        if (! $media) {
            return '-';
        }
        $name = e($media->title ?: $media->original_name);

        return $media->isImage()
            ? '<span style="display:flex;align-items:center;gap:8px"><img src="'.e($media->url(480)).'" alt="" style="width:40px;height:28px;object-fit:cover;border-radius:4px">'.$name.'</span>'
            : $name;
    }
}
