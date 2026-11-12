<?php

namespace App\Builder\Style;

/**
 * Whitelist nilai desain → kelas Tailwind tetap. Tidak ada CSS mentah dari input pengguna.
 * Semua string kelas ditulis literal di file ini agar terdeteksi oleh Tailwind (@source).
 */
final class StyleTokens
{
    public const BACKGROUNDS = [
        'none' => 'Transparan', 'white' => 'Putih', 'surface' => 'Abu muda', 'primary' => 'Primary (navy)',
        'secondary' => 'Secondary (biru)', 'accent' => 'Accent (cyan)', 'dark' => 'Gelap', 'gradient' => 'Gradien brand',
        'custom' => 'Warna kustom',
    ];

    private const BACKGROUND_CLASSES = [
        'none' => '', 'white' => 'bg-white', 'surface' => 'bg-surface', 'primary' => 'bg-primary',
        'secondary' => 'bg-secondary', 'accent' => 'bg-accent', 'dark' => 'bg-slate-900',
        'gradient' => 'bg-gradient-to-br from-primary via-secondary to-accent', 'custom' => '',
    ];

    public const TEXT_COLORS = ['auto' => 'Otomatis', 'dark' => 'Gelap', 'light' => 'Terang', 'custom' => 'Warna kustom'];

    private const TEXT_CLASSES = ['auto' => '', 'dark' => 'text-ink', 'light' => 'text-white', 'custom' => ''];

    public const SPACING = ['none' => 'Tidak ada', 'sm' => 'Kecil', 'md' => 'Sedang', 'lg' => 'Besar', 'xl' => 'Sangat besar'];

    private const PADDING_Y = [
        'mobile' => ['none' => 'py-0', 'sm' => 'py-6', 'md' => 'py-10', 'lg' => 'py-14', 'xl' => 'py-20'],
        'tablet' => ['none' => 'md:py-0', 'sm' => 'md:py-8', 'md' => 'md:py-12', 'lg' => 'md:py-16', 'xl' => 'md:py-24'],
        'desktop' => ['none' => 'lg:py-0', 'sm' => 'lg:py-10', 'md' => 'lg:py-16', 'lg' => 'lg:py-24', 'xl' => 'lg:py-32'],
    ];

    private const MARGIN_TOP = ['none' => '', 'sm' => 'mt-4', 'md' => 'mt-8', 'lg' => 'mt-12', 'xl' => 'mt-20'];

    private const MARGIN_BOTTOM = ['none' => '', 'sm' => 'mb-4', 'md' => 'mb-8', 'lg' => 'mb-12', 'xl' => 'mb-20'];

    public const ALIGNMENTS = ['left' => 'Kiri', 'center' => 'Tengah', 'right' => 'Kanan'];

    private const ALIGN = [
        'mobile' => ['left' => 'text-left', 'center' => 'text-center', 'right' => 'text-right'],
        'tablet' => ['left' => 'md:text-left', 'center' => 'md:text-center', 'right' => 'md:text-right'],
        'desktop' => ['left' => 'lg:text-left', 'center' => 'lg:text-center', 'right' => 'lg:text-right'],
    ];

    public const CONTAINERS = ['narrow' => 'Sempit', 'normal' => 'Normal', 'wide' => 'Lebar', 'full' => 'Penuh'];

    private const CONTAINER = [
        'narrow' => 'mx-auto max-w-3xl px-4 sm:px-6', 'normal' => 'mx-auto max-w-6xl px-4 sm:px-6 lg:px-8',
        'wide' => 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8', 'full' => 'w-full',
    ];

    public const HEIGHTS = ['auto' => 'Otomatis', 'sm' => 'Pendek', 'md' => 'Sedang', 'lg' => 'Tinggi', 'screen' => 'Layar penuh'];

    private const HEIGHT = [
        'auto' => '', 'sm' => 'min-h-[320px]', 'md' => 'min-h-[460px]', 'lg' => 'min-h-[600px]', 'screen' => 'min-h-screen',
    ];

    public const BUTTON_STYLES = ['primary' => 'Primary', 'secondary' => 'Secondary', 'accent' => 'Accent', 'outline' => 'Outline', 'light' => 'Terang', 'ghost' => 'Ghost'];

    private const BUTTON = [
        'primary' => 'bg-primary text-white hover:bg-primary/90',
        'secondary' => 'bg-secondary text-white hover:bg-secondary/90',
        'accent' => 'bg-accent text-primary hover:bg-accent/90',
        'outline' => 'border border-current hover:bg-black/5',
        'light' => 'bg-white text-primary hover:bg-white/90',
        'ghost' => 'hover:bg-black/5 underline-offset-4 hover:underline',
    ];

    public const OVERLAYS = ['none' => 'Tanpa overlay', 'light' => 'Terang', 'dark' => 'Gelap', 'brand' => 'Warna brand'];

    private const OVERLAY = [
        'none' => '', 'light' => 'bg-white/70', 'dark' => 'bg-slate-950/60', 'brand' => 'bg-primary/80',
    ];

    public const CARD_STYLES = ['flat' => 'Flat', 'bordered' => 'Bordered', 'elevated' => 'Elevated'];

    private const CARD = [
        'flat' => 'bg-surface', 'bordered' => 'bg-white border border-slate-200', 'elevated' => 'bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-900/5',
    ];

    private const COLUMNS = [
        'mobile' => [1 => 'grid-cols-1', 2 => 'grid-cols-2', 3 => 'grid-cols-3', 4 => 'grid-cols-4', 5 => 'grid-cols-5', 6 => 'grid-cols-6'],
        'tablet' => [1 => 'md:grid-cols-1', 2 => 'md:grid-cols-2', 3 => 'md:grid-cols-3', 4 => 'md:grid-cols-4', 5 => 'md:grid-cols-5', 6 => 'md:grid-cols-6'],
        'desktop' => [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4', 5 => 'lg:grid-cols-5', 6 => 'lg:grid-cols-6'],
    ];

    private const VISIBILITY = [
        'mobile' => [true => 'block', false => 'hidden'],
        'tablet' => [true => 'md:block', false => 'md:hidden'],
        'desktop' => [true => 'lg:block', false => 'lg:hidden'],
    ];

    public const DEVICES = ['desktop', 'tablet', 'mobile'];

    public const HEX_PATTERN = '/^#[0-9A-Fa-f]{6}$/';

    public static function keys(array $options): string
    {
        return implode(',', array_keys($options));
    }

    public static function background(?string $token): string
    {
        return self::BACKGROUND_CLASSES[$token] ?? '';
    }

    /** Warna teks otomatis: latar gelap → teks terang. */
    public static function text(?string $token, ?string $background = null): string
    {
        if (($token ?? 'auto') === 'auto') {
            return in_array($background, ['primary', 'secondary', 'dark', 'gradient'], true) ? 'text-white' : 'text-ink';
        }

        return self::TEXT_CLASSES[$token] ?? '';
    }

    public static function isDark(?string $background): bool
    {
        return in_array($background, ['primary', 'secondary', 'dark', 'gradient'], true);
    }

    public static function container(?string $token): string
    {
        return self::CONTAINER[$token] ?? self::CONTAINER['normal'];
    }

    public static function height(?string $token): string
    {
        return self::HEIGHT[$token] ?? '';
    }

    public static function button(?string $token): string
    {
        return 'inline-flex items-center justify-center gap-2 rounded-lg px-5 py-3 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent '.(self::BUTTON[$token] ?? self::BUTTON['primary']);
    }

    public static function overlay(?string $token): string
    {
        return self::OVERLAY[$token] ?? '';
    }

    public static function card(?string $token): string
    {
        return self::CARD[$token] ?? self::CARD['elevated'];
    }

    public static function marginTop(?string $token): string
    {
        return self::MARGIN_TOP[$token] ?? '';
    }

    public static function marginBottom(?string $token): string
    {
        return self::MARGIN_BOTTOM[$token] ?? '';
    }

    /** Padding vertikal responsif: nilai device mewarisi device yang lebih besar bila kosong. */
    public static function paddingY(array $design, array $responsive): string
    {
        return self::perDevice(self::PADDING_Y, $design['padding_y'] ?? 'lg', $responsive, 'padding_y');
    }

    public static function align(array $design, array $responsive): string
    {
        return self::perDevice(self::ALIGN, $design['text_align'] ?? 'left', $responsive, 'text_align');
    }

    public static function columns(array $responsive, int $defaultDesktop = 3, int $defaultTablet = 2, int $defaultMobile = 1): string
    {
        $value = fn (string $device, int $default) => max(1, min(6, (int) ($responsive[$device]['columns'] ?? $default)));

        return implode(' ', [
            self::COLUMNS['mobile'][$value('mobile', $defaultMobile)],
            self::COLUMNS['tablet'][$value('tablet', $defaultTablet)],
            self::COLUMNS['desktop'][$value('desktop', $defaultDesktop)],
        ]);
    }

    /** Kelas visibilitas per device (mobile = base, tablet = md, desktop = lg). */
    public static function visibility(array $responsive): string
    {
        $visible = fn (string $device) => (bool) ($responsive[$device]['visible'] ?? true);
        if ($visible('mobile') && $visible('tablet') && $visible('desktop')) {
            return '';
        }

        return implode(' ', [
            self::VISIBILITY['mobile'][$visible('mobile')],
            self::VISIBILITY['tablet'][$visible('tablet')],
            self::VISIBILITY['desktop'][$visible('desktop')],
        ]);
    }

    /** Warna hex tervalidasi atau null. Dipakai sebagai lapisan kedua saat render. */
    public static function hex(?string $value): ?string
    {
        return is_string($value) && preg_match(self::HEX_PATTERN, $value) ? strtoupper($value) : null;
    }

    private static function perDevice(array $map, string $base, array $responsive, string $field): string
    {
        $desktop = $responsive['desktop'][$field] ?? $base;
        $tablet = $responsive['tablet'][$field] ?? $desktop;
        $mobile = $responsive['mobile'][$field] ?? $tablet;

        return implode(' ', array_filter([
            $map['mobile'][$mobile] ?? '',
            $map['tablet'][$tablet] ?? '',
            $map['desktop'][$desktop] ?? '',
        ]));
    }
}
