@php
    $settings = app(\App\Support\SettingsRepository::class);
    $menu = app(\App\Support\NavigationRepository::class)->menu('header');
    $header = $settings->group('header');
    $ctaUrl = \App\Support\UrlGuard::sanitize($header['cta_url'] ?? '/kontak', '/kontak');
    $current = '/'.ltrim(request()->path(), '/');
    $isActive = fn ($url) => $url === url($current) || $url === $current || (parse_url($url, PHP_URL_PATH) ?: '/') === $current;
@endphp
<header x-data="{ open: false }" @keydown.escape.window="open = false"
    class="{{ ($header['sticky'] ?? true) ? 'sticky top-0' : 'relative' }} z-50 border-b border-slate-200/70 bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <x-public.logo />

        <nav class="hidden items-center gap-1 lg:flex" aria-label="Menu utama">
            @foreach ($menu as $item)
                @if (empty($item['children']))
                    <a href="{{ $item['url'] }}" @if ($item['new_tab']) target="_blank" rel="noopener noreferrer" @endif
                        class="rounded-md px-3 py-2 text-sm font-medium transition hover:bg-surface hover:text-secondary {{ $isActive($item['url']) ? 'text-secondary' : 'text-ink' }}">{{ $item['label'] }}</a>
                @else
                    <div x-data="{ sub: false }" class="relative" @mouseenter="sub = true" @mouseleave="sub = false">
                        <a href="{{ $item['url'] }}" @focus="sub = true" :aria-expanded="sub" aria-haspopup="true"
                            class="flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium text-ink transition hover:bg-surface hover:text-secondary">
                            {{ $item['label'] }}
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                        </a>
                        <div x-show="sub" x-cloak x-transition.opacity class="absolute left-0 top-full w-64 pt-2">
                            <div class="rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                                @foreach ($item['children'] as $child)
                                    <a href="{{ $child['url'] }}" @focus="sub = true" @if ($child['new_tab']) target="_blank" rel="noopener noreferrer" @endif
                                        class="block rounded-lg px-3 py-2 text-sm text-ink hover:bg-surface hover:text-secondary">{{ $child['label'] }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            @if (filled($header['cta_label'] ?? null))
                <span class="hidden sm:block"><a href="{{ $ctaUrl }}" class="{{ \App\Builder\Style\StyleTokens::button('primary') }}">{{ $header['cta_label'] }}</a></span>
            @endif
            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-ink hover:bg-surface lg:hidden"
                @click="open = !open" :aria-expanded="open" aria-controls="mobile-menu" aria-label="Buka menu">
                <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg x-show="open" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    </div>

    <div id="mobile-menu" x-show="open" x-cloak x-transition class="max-h-[calc(100vh-4rem)] overflow-y-auto border-t border-slate-200 bg-white lg:hidden">
        <nav class="space-y-1 px-4 py-4" aria-label="Menu mobile">
            @foreach ($menu as $item)
                @if (empty($item['children']))
                    <a href="{{ $item['url'] }}" class="block rounded-lg px-3 py-3 text-base font-medium text-ink hover:bg-surface">{{ $item['label'] }}</a>
                @else
                    <div x-data="{ sub: false }">
                        <button type="button" @click="sub = !sub" :aria-expanded="sub" class="flex w-full items-center justify-between rounded-lg px-3 py-3 text-left text-base font-medium text-ink hover:bg-surface">
                            {{ $item['label'] }}
                            <svg class="h-5 w-5 transition" :class="sub && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                        </button>
                        <div x-show="sub" x-cloak class="ml-3 space-y-1 border-l border-slate-200 pl-3">
                            <a href="{{ $item['url'] }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-secondary">Semua {{ $item['label'] }}</a>
                            @foreach ($item['children'] as $child)
                                <a href="{{ $child['url'] }}" class="block rounded-lg px-3 py-2 text-sm text-ink hover:bg-surface">{{ $child['label'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
            @if (filled($header['cta_label'] ?? null))
                <a href="{{ $ctaUrl }}" class="mt-3 flex w-full {{ \App\Builder\Style\StyleTokens::button('primary') }}">{{ $header['cta_label'] }}</a>
            @endif
        </nav>
    </div>
</header>
