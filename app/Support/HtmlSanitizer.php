<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonySanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Sanitasi HTML berbasis allowlist (mencegah stored XSS).
 * - richText(): untuk editor konten (paragraf, heading, list, link).
 * - customHtml(): blok Custom HTML (role khusus) — lebih banyak elemen layout, tetap tanpa
 *   script, iframe, style, atau event handler.
 */
final class HtmlSanitizer
{
    private const LINK_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    private static ?SymfonySanitizer $rich = null;

    private static ?SymfonySanitizer $custom = null;

    public static function richText(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        self::$rich ??= new SymfonySanitizer(self::base()
            ->allowElement('p')->allowElement('br')->allowElement('strong')->allowElement('b')
            ->allowElement('em')->allowElement('i')->allowElement('u')->allowElement('s')
            ->allowElement('ul')->allowElement('ol')->allowElement('li')
            ->allowElement('h2')->allowElement('h3')->allowElement('h4')
            ->allowElement('blockquote')->allowElement('code')->allowElement('pre')->allowElement('hr')
            ->allowElement('a', ['href', 'title', 'target'])
            ->withMaxInputLength(60_000));

        return self::$rich->sanitize($html);
    }

    public static function customHtml(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $config = self::base()->withMaxInputLength(60_000);
        foreach (['div', 'section', 'article', 'span', 'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'small',
            'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'h5', 'blockquote', 'code', 'pre', 'hr',
            'table', 'thead', 'tbody', 'tr', 'th', 'td', 'figure', 'figcaption'] as $element) {
            $config = $config->allowElement($element, ['class']);
        }
        $config = $config
            ->allowElement('a', ['href', 'title', 'target', 'class'])
            ->allowElement('img', ['src', 'alt', 'width', 'height', 'class', 'loading'])
            ->allowMediaSchemes(['https'])
            ->allowRelativeMedias();

        self::$custom ??= new SymfonySanitizer($config);

        return self::$custom->sanitize($html);
    }

    /** Teks polos: hapus seluruh tag. */
    public static function plain(?string $text): string
    {
        return trim(strip_tags((string) $text));
    }

    private static function base(): HtmlSanitizerConfig
    {
        return (new HtmlSanitizerConfig)
            ->allowLinkSchemes(self::LINK_SCHEMES)
            ->allowRelativeLinks()
            ->forceAttribute('a', 'rel', 'noopener noreferrer');
    }
}
