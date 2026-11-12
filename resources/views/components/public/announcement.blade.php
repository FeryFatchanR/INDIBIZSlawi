@php
    $a = app(\App\Support\SettingsRepository::class)->group('announcement');
    $styles = ['accent' => 'bg-accent text-primary', 'primary' => 'bg-primary text-white', 'secondary' => 'bg-secondary text-white', 'dark' => 'bg-slate-900 text-white'];
@endphp
@if (($a['enabled'] ?? false) && filled($a['text'] ?? null))
    <div class="{{ $styles[$a['style'] ?? 'accent'] ?? $styles['accent'] }} px-4 py-2 text-center text-sm" role="region" aria-label="Pengumuman">
        <span>{{ $a['text'] }}</span>
        @if (filled($a['link_url'] ?? null) && \App\Support\UrlGuard::isSafe($a['link_url']))
            <a href="{{ $a['link_url'] }}" class="ml-2 font-semibold underline underline-offset-2">{{ $a['link_label'] ?: 'Selengkapnya' }}</a>
        @endif
    </div>
@endif
