<?php

namespace App\Builder;

use App\Builder\Contracts\SectionType;
use App\Builder\Exceptions\InvalidSectionException;

/**
 * Allowlist terpusat tipe section. Hanya tipe yang terdaftar di sini yang dapat disimpan dan dirender.
 */
class SectionRegistry
{
    /** @var array<string, SectionType> */
    private array $types = [];

    /** @param list<class-string<SectionType>> $classes */
    public function __construct(array $classes = [])
    {
        foreach ($classes as $class) {
            $this->register(new $class);
        }
    }

    public function register(SectionType $type): void
    {
        $this->types[$type->key()] = $type;
    }

    public function has(?string $key): bool
    {
        return $key !== null && isset($this->types[$key]);
    }

    public function get(string $key): SectionType
    {
        return $this->types[$key] ?? throw new InvalidSectionException("Tipe section [{$key}] tidak dikenal.");
    }

    /** @return array<string, SectionType> */
    public function all(): array
    {
        return $this->types;
    }

    /** @return array<string, array<string, SectionType>> dikelompokkan per kategori */
    public function grouped(): array
    {
        $groups = [];
        foreach ($this->types as $key => $type) {
            $groups[$type->category()][$key] = $type;
        }

        return $groups;
    }

    /** @return list<class-string<SectionType>> */
    public static function defaultTypes(): array
    {
        return [
            Sections\HeroSection::class,
            Sections\BannerSection::class,
            Sections\HeadingSection::class,
            Sections\RichTextSection::class,
            Sections\ImageSection::class,
            Sections\GallerySection::class,
            Sections\VideoSection::class,
            Sections\ProductCardsSection::class,
            Sections\PricingCardsSection::class,
            Sections\FeaturesSection::class,
            Sections\StatisticsSection::class,
            Sections\TestimonialsSection::class,
            Sections\LogoCarouselSection::class,
            Sections\IndustrySolutionsSection::class,
            Sections\CaseStudiesSection::class,
            Sections\FaqSection::class,
            Sections\ContactFormSection::class,
            Sections\CtaSection::class,
            Sections\MapSection::class,
            Sections\SpacerSection::class,
            Sections\DividerSection::class,
            Sections\GlobalSectionReference::class,
            Sections\CustomHtmlSection::class,
        ];
    }
}
