<?php

namespace App\Builder\Contracts;

use App\Builder\Style\StyleTokens;
use App\Rules\SafeUrl;

/**
 * Definisi satu tipe section builder. Setiap tipe menentukan sendiri skema konten, aturan
 * validasi, field property editor, dan template Blade (hard-coded, tidak pernah dari database).
 */
abstract class SectionType
{
    abstract public function key(): string;

    abstract public function label(): string;

    /** Nama view Blade tetap, mis. 'builder.hero'. */
    abstract public function view(): string;

    /** @return array<string, mixed> aturan validasi Laravel relatif terhadap `content` */
    abstract public function contentRules(): array;

    /** @return array<int, \Filament\Support\Components\Component> field editor untuk `content` */
    abstract public function contentFields(): array;

    public function category(): string
    {
        return 'Konten';
    }

    public function icon(): string
    {
        return 'heroicon-o-square-3-stack-3d';
    }

    public function description(): string
    {
        return '';
    }

    public function schemaVersion(): int
    {
        return 1;
    }

    /** Permission tambahan yang wajib dimiliki untuk menambah/mengubah tipe ini. */
    public function permission(): ?string
    {
        return null;
    }

    public function supportsColumns(): bool
    {
        return false;
    }

    /** @return array{desktop:int, tablet:int, mobile:int} */
    public function defaultColumns(): array
    {
        return ['desktop' => 3, 'tablet' => 2, 'mobile' => 1];
    }

    /** @return array<string, mixed> */
    public function defaultContent(): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function defaultDesign(): array
    {
        return [];
    }

    /** @return array<string, mixed> aturan tambahan khusus tipe, relatif terhadap `design` */
    public function designRules(): array
    {
        return [];
    }

    /** @return array<int, \Filament\Support\Components\Component> field desain tambahan khusus tipe */
    public function designFields(): array
    {
        return [];
    }

    /** @return list<string> path (dot, mendukung *) di `content` yang berisi rich text HTML */
    public function richTextFields(): array
    {
        return [];
    }

    /** @return list<string> path (dot, mendukung *) di `content` yang berisi ID media_assets */
    public function mediaFields(): array
    {
        return [];
    }

    /**
     * Data tambahan untuk view (mis. query produk). Dipanggil saat render, tidak disimpan.
     *
     * @return array<string, mixed>
     */
    public function prepare(array $content, array $design): array
    {
        return [];
    }

    /** Migrasi data dari schema_version lama ke versi terbaru. */
    public function upgrade(array $section): array
    {
        $section['schema_version'] = $this->schemaVersion();

        return $section;
    }

    /** @return array<string, mixed> */
    final public function defaults(): array
    {
        $columns = $this->defaultColumns();
        $responsive = [];
        foreach (StyleTokens::DEVICES as $device) {
            $responsive[$device] = ['visible' => true];
            if ($this->supportsColumns()) {
                $responsive[$device]['columns'] = $columns[$device];
            }
        }

        return [
            'content' => $this->defaultContent(),
            'design' => array_merge([
                'background' => 'none', 'text_color' => 'auto', 'padding_y' => 'lg', 'margin_top' => 'none',
                'margin_bottom' => 'none', 'text_align' => 'left', 'container' => 'normal', 'anchor_id' => null,
            ], $this->defaultDesign()),
            'responsive' => $responsive,
        ];
    }

    /** Aturan desain umum untuk semua section (seluruhnya whitelist). */
    final public function commonDesignRules(): array
    {
        return [
            'background' => ['nullable', 'in:'.StyleTokens::keys(StyleTokens::BACKGROUNDS)],
            'background_color' => ['nullable', 'regex:'.StyleTokens::HEX_PATTERN],
            'text_color' => ['nullable', 'in:'.StyleTokens::keys(StyleTokens::TEXT_COLORS)],
            'text_hex' => ['nullable', 'regex:'.StyleTokens::HEX_PATTERN],
            'padding_y' => ['nullable', 'in:'.StyleTokens::keys(StyleTokens::SPACING)],
            'margin_top' => ['nullable', 'in:'.StyleTokens::keys(StyleTokens::SPACING)],
            'margin_bottom' => ['nullable', 'in:'.StyleTokens::keys(StyleTokens::SPACING)],
            'text_align' => ['nullable', 'in:'.StyleTokens::keys(StyleTokens::ALIGNMENTS)],
            'container' => ['nullable', 'in:'.StyleTokens::keys(StyleTokens::CONTAINERS)],
            'anchor_id' => ['nullable', 'string', 'max:60', 'regex:/^[a-z][a-z0-9\-]*$/'],
        ];
    }

    /** Aturan responsif per device. */
    final public function responsiveRules(): array
    {
        $rules = [];
        foreach (StyleTokens::DEVICES as $device) {
            $rules["{$device}"] = ['nullable', 'array'];
            $rules["{$device}.visible"] = ['nullable', 'boolean'];
            $rules["{$device}.padding_y"] = ['nullable', 'in:'.StyleTokens::keys(StyleTokens::SPACING)];
            $rules["{$device}.text_align"] = ['nullable', 'in:'.StyleTokens::keys(StyleTokens::ALIGNMENTS)];
            if ($this->supportsColumns()) {
                $rules["{$device}.columns"] = ['nullable', 'integer', 'between:1,6'];
            }
        }

        return $rules;
    }

    /** Helper aturan tombol (label + URL aman). */
    protected function buttonRules(string $prefix): array
    {
        return [
            "{$prefix}" => ['nullable', 'array'],
            "{$prefix}.label" => ['nullable', 'string', 'max:60'],
            "{$prefix}.url" => ['nullable', 'string', 'max:2048', new SafeUrl],
            "{$prefix}.style" => ['nullable', 'in:'.StyleTokens::keys(StyleTokens::BUTTON_STYLES)],
            "{$prefix}.new_tab" => ['nullable', 'boolean'],
        ];
    }

    protected function mediaRule(): array
    {
        return ['nullable', 'integer', 'exists:media_assets,id'];
    }
}
