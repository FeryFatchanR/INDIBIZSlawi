<?php

namespace App\Builder\Style;

/** Allowlist ikon (Heroicons outline) yang boleh dipilih di builder. */
final class Icons
{
    public const OPTIONS = [
        'wifi' => 'Wi-Fi', 'globe-alt' => 'Globe', 'server-stack' => 'Server', 'cpu-chip' => 'Chip',
        'signal' => 'Sinyal', 'bolt' => 'Kecepatan', 'shield-check' => 'Keamanan', 'lock-closed' => 'Gembok',
        'clock' => 'Jam', 'lifebuoy' => 'Support', 'phone' => 'Telepon', 'chat-bubble-left-right' => 'Chat',
        'building-office-2' => 'Gedung', 'building-library' => 'Instansi', 'academic-cap' => 'Pendidikan',
        'heart' => 'Kesehatan', 'banknotes' => 'Perbankan', 'home-modern' => 'Hotel', 'wrench-screwdriver' => 'Industri',
        'shopping-bag' => 'Retail', 'briefcase' => 'Enterprise', 'video-camera' => 'CCTV', 'cloud' => 'Cloud',
        'chart-bar' => 'Grafik', 'check-badge' => 'Sertifikasi', 'map-pin' => 'Lokasi', 'users' => 'Tim',
        'cog-6-tooth' => 'Konfigurasi', 'arrows-right-left' => 'Koneksi', 'rocket-launch' => 'Roket',
        'currency-dollar' => 'Harga', 'sparkles' => 'Unggulan', 'star' => 'Bintang', 'link' => 'Tautan',
    ];

    public static function valid(?string $name): ?string
    {
        return $name !== null && array_key_exists($name, self::OPTIONS) ? $name : null;
    }

    public static function svg(?string $name, string $class = 'h-6 w-6'): string
    {
        $name = self::valid($name) ?? 'sparkles';

        return svg('heroicon-o-'.$name, $class)->toHtml();
    }
}
