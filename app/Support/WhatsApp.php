<?php

namespace App\Support;

use App\Models\Campaign;
use App\Models\Product;

final class WhatsApp
{
    /** Nomor dinormalisasi ke format internasional tanpa simbol (62xxx). */
    public static function number(): ?string
    {
        $raw = preg_replace('/\D+/', '', (string) app(SettingsRepository::class)->get('contact.whatsapp'));
        if ($raw === '' || $raw === null) {
            return null;
        }
        if (str_starts_with($raw, '0')) {
            $raw = '62'.substr($raw, 1);
        }

        return strlen($raw) >= 9 && strlen($raw) <= 15 ? $raw : null;
    }

    public static function enabled(): bool
    {
        return self::number() !== null;
    }

    /** Link internal /wa (dicatat sebagai sumber kampanye), lalu redirect ke wa.me. */
    public static function link(?Product $product = null, ?Campaign $campaign = null): string
    {
        return route('whatsapp', array_filter([
            'product' => $product?->slug,
            'campaign' => $campaign?->slug,
        ]));
    }

    public static function directUrl(?string $message = null): ?string
    {
        $number = self::number();
        if (! $number) {
            return null;
        }
        $message ??= (string) app(SettingsRepository::class)->get('contact.whatsapp_default_message');

        return 'https://wa.me/'.$number.($message !== '' ? '?text='.rawurlencode($message) : '');
    }
}
