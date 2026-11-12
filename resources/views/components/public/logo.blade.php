@props(['light' => false])
@php
    $settings = app(\App\Support\SettingsRepository::class);
    $logo = \App\Models\MediaAsset::find($settings->get('general.logo_id'));
    $name = $settings->get('general.site_name');
@endphp
<a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2" aria-label="{{ $name }} — Beranda">
    @if ($logo)
        <img src="{{ $logo->url(480) }}" alt="{{ $name }}" class="h-9 w-auto" width="{{ $logo->width }}" height="{{ $logo->height }}">
    @else
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-secondary to-accent text-white">
            {!! \App\Builder\Style\Icons::svg('signal', 'h-5 w-5') !!}
        </span>
        <span class="truncate font-display text-base font-bold sm:text-lg {{ $light ? 'text-white' : 'text-primary' }}">{{ $name }}</span>
    @endif
</a>
