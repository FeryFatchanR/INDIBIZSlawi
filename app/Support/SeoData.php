<?php

namespace App\Support;

use App\Models\MediaAsset;

/** Data SEO untuk satu halaman publik. */
final class SeoData
{
    /** @param array<int, array<string, mixed>> $jsonLd */
    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $canonical = null,
        public ?string $image = null,
        public string $type = 'website',
        public bool $noindex = false,
        public array $breadcrumbs = [],
        public array $jsonLd = [],
    ) {}

    public static function make(string $title, ?string $description = null, ?MediaAsset $image = null): self
    {
        $settings = app(SettingsRepository::class);
        $siteName = (string) $settings->get('general.site_name');
        $suffix = (string) $settings->get('seo.title_suffix');

        $fullTitle = $title === $siteName || $title === '' ? $siteName : $title.$suffix;
        $imageAsset = $image ?? MediaAsset::find($settings->get('seo.default_og_image_id'));

        return new self(
            title: $fullTitle,
            description: $description ?: (string) $settings->get('seo.default_description'),
            canonical: url()->current(),
            image: $imageAsset?->url(1440),
        );
    }

    /** @param list<array{0:string,1:?string}> $items [label, url] */
    public function withBreadcrumbs(array $items): self
    {
        $this->breadcrumbs = $items;
        $this->jsonLd[] = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn ($item, $i) => array_filter([
                '@type' => 'ListItem', 'position' => $i + 1, 'name' => $item[0], 'item' => $item[1] ?? null,
            ]))->all(),
        ];

        return $this;
    }

    public function addJsonLd(array $data): self
    {
        $this->jsonLd[] = ['@context' => 'https://schema.org'] + $data;

        return $this;
    }
}
