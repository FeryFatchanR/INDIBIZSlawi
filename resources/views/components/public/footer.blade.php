@php
    $settings = app(\App\Support\SettingsRepository::class);
    $nav = app(\App\Support\NavigationRepository::class);
    $contact = $settings->group('contact');
    $social = collect($settings->group('social'))->filter(fn ($url) => filled($url) && \App\Support\UrlGuard::isSafe($url));
    $copyright = str_replace('{year}', (string) now()->year, (string) $settings->get('footer.copyright'));
@endphp
<footer class="bg-primary text-white/80">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-2 lg:grid-cols-4 lg:px-8">
        <div>
            <x-public.logo :light="true" />
            <p class="mt-4 text-sm leading-relaxed">{{ $settings->get('footer.about') }}</p>
            @if ($social->isNotEmpty())
                <div class="mt-5 flex flex-wrap gap-3">
                    @foreach ($social as $network => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="rounded-md bg-white/10 px-3 py-1.5 text-xs font-semibold capitalize hover:bg-white/20">{{ $network }}</a>
                    @endforeach
                </div>
            @endif
        </div>
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wider text-white">Layanan</h2>
            <ul class="mt-4 space-y-2 text-sm">
                @foreach ($nav->menu('footer_products') as $item)
                    <li><a href="{{ $item['url'] }}" class="hover:text-accent">{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </div>
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wider text-white">Perusahaan</h2>
            <ul class="mt-4 space-y-2 text-sm">
                @foreach ($nav->menu('footer_company') as $item)
                    <li><a href="{{ $item['url'] }}" class="hover:text-accent">{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </div>
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wider text-white">Kontak</h2>
            <ul class="mt-4 space-y-3 text-sm">
                @if (filled($contact['address'] ?? null))<li class="flex gap-2">{!! \App\Builder\Style\Icons::svg('map-pin', 'h-5 w-5 shrink-0 text-accent') !!}<span>{{ $contact['address'] }}</span></li>@endif
                @if (filled($contact['phone'] ?? null))<li class="flex gap-2">{!! \App\Builder\Style\Icons::svg('phone', 'h-5 w-5 shrink-0 text-accent') !!}<a href="tel:{{ preg_replace('/[^\d+]/', '', $contact['phone']) }}" class="hover:text-accent">{{ $contact['phone'] }}</a></li>@endif
                @if (filled($contact['email'] ?? null))<li class="flex gap-2">{!! \App\Builder\Style\Icons::svg('chat-bubble-left-right', 'h-5 w-5 shrink-0 text-accent') !!}<a href="mailto:{{ $contact['email'] }}" class="hover:text-accent">{{ $contact['email'] }}</a></li>@endif
                @if (filled($contact['business_hours'] ?? null))<li class="flex gap-2">{!! \App\Builder\Style\Icons::svg('clock', 'h-5 w-5 shrink-0 text-accent') !!}<span>{{ $contact['business_hours'] }}</span></li>@endif
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="mx-auto max-w-7xl px-4 py-5 text-xs text-white/60 sm:px-6 lg:px-8">{{ $copyright }}</div>
    </div>
</footer>
