<?php

namespace App\Builder\Sections;

use App\Builder\Contracts\SectionType;
use App\Builder\Fields;

class MapSection extends SectionType
{
    /** Hanya embed Google Maps resmi yang diizinkan. */
    public const EMBED_PATTERN = '/^https:\/\/www\.google\.com\/maps\/embed\?pb=[A-Za-z0-9!._\-%]+$/';

    public function key(): string
    {
        return 'map';
    }

    public function label(): string
    {
        return 'Map';
    }

    public function category(): string
    {
        return 'Media';
    }

    public function icon(): string
    {
        return 'heroicon-o-map';
    }

    public function view(): string
    {
        return 'builder.map';
    }

    public function defaultContent(): array
    {
        return ['heading' => 'Lokasi Kami', 'mode' => 'settings', 'embed_url' => null, 'query' => null];
    }

    public function defaultDesign(): array
    {
        return ['map_height' => 'md'];
    }

    public function contentRules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:200'],
            'mode' => ['required', 'in:settings,embed,query'],
            'embed_url' => ['nullable', 'required_if:mode,embed', 'string', 'max:2048', 'regex:'.self::EMBED_PATTERN],
            'query' => ['nullable', 'required_if:mode,query', 'string', 'max:200'],
        ];
    }

    public function designRules(): array
    {
        return ['map_height' => ['nullable', 'in:sm,md,lg']];
    }

    public function contentFields(): array
    {
        return [
            Fields::text('heading', 'Judul', 200),
            Fields::select('mode', 'Sumber peta', ['settings' => 'Dari Website Settings (alamat kantor)', 'embed' => 'URL embed Google Maps', 'query' => 'Cari alamat'])->live(),
            Fields::text('embed_url', 'URL embed (https://www.google.com/maps/embed?pb=...)', 2048)->visible(fn ($get) => $get('mode') === 'embed'),
            Fields::text('query', 'Alamat / nama tempat', 200)->visible(fn ($get) => $get('mode') === 'query'),
        ];
    }

    public function designFields(): array
    {
        return [Fields::select('map_height', 'Tinggi peta', ['sm' => 'Pendek', 'md' => 'Sedang', 'lg' => 'Tinggi'])];
    }

    /** URL embed final yang aman (null jika tidak valid). */
    public static function resolveEmbedUrl(array $content, ?string $settingsUrl): ?string
    {
        return match ($content['mode'] ?? 'settings') {
            'embed' => preg_match(self::EMBED_PATTERN, (string) ($content['embed_url'] ?? '')) ? $content['embed_url'] : null,
            'query' => filled($content['query'] ?? null) ? 'https://maps.google.com/maps?output=embed&q='.rawurlencode($content['query']) : null,
            default => $settingsUrl && preg_match(self::EMBED_PATTERN, $settingsUrl) ? $settingsUrl : null,
        };
    }
}
