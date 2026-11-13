@php
    use App\Builder\Style\StyleTokens;
    $bg = $media->get($content['background_image_id'] ?? null);
    $side = $media->get($content['image_id'] ?? null);
    $position = $design['image_position'] ?? 'right';
    $showSide = $side && $position !== 'none';
@endphp
@if ($bg)
    <div class="absolute inset-0 overflow-hidden" aria-hidden="true">
        @include('builder.partials.image', ['asset' => $bg, 'class' => 'h-full w-full object-cover', 'alt' => '', 'eager' => true])
        <div class="absolute inset-0 {{ StyleTokens::overlay($design['overlay'] ?? 'brand') }}"></div>
    </div>
@endif
<div class="relative {{ StyleTokens::container($design['container'] ?? 'normal') }} {{ StyleTokens::height($design['height'] ?? 'md') }} flex items-center">
    <div class="grid w-full items-center gap-10 {{ $showSide ? 'lg:grid-cols-2' : '' }}">
        <div class="{{ StyleTokens::align($design, $responsive) }} {{ $showSide && $position === 'left' ? 'lg:order-2' : '' }} {{ ! $showSide && ($design['text_align'] ?? 'left') === 'center' ? 'mx-auto max-w-3xl' : 'max-w-2xl' }}">
            @if (filled($content['eyebrow'] ?? null))
                <span class="mb-4 inline-flex items-center rounded-full {{ $isDark || $bg ? 'bg-white/10 text-white ring-1 ring-white/20' : 'bg-accent/10 text-secondary' }} px-3 py-1 text-xs font-semibold uppercase tracking-wider" @if ($editor) data-inline-edit="eyebrow" @endif>{{ $content['eyebrow'] }}</span>
            @endif
            <h1 class="text-4xl font-extrabold leading-tight sm:text-5xl lg:text-6xl" @if ($editor) data-inline-edit="heading" @endif>{{ $content['heading'] ?? '' }}</h1>
            @if (filled($content['subtitle'] ?? null))
                <p class="mt-5 text-lg leading-relaxed opacity-90 md:text-xl" @if ($editor) data-inline-edit="subtitle" @endif>{{ $content['subtitle'] }}</p>
            @endif
            @if (filled($content['description'] ?? null))
                <p class="mt-4 text-base leading-relaxed opacity-80">{{ $content['description'] }}</p>
            @endif
            <div class="mt-8 flex flex-wrap gap-3 {{ ($design['text_align'] ?? 'left') === 'center' ? 'justify-center' : '' }}">
                @include('builder.partials.button', ['button' => $content['primary_button'] ?? [], 'default' => 'accent'])
                @include('builder.partials.button', ['button' => $content['secondary_button'] ?? [], 'default' => 'outline'])
            </div>
        </div>
        @if ($showSide)
            <div class="{{ $position === 'left' ? 'lg:order-1' : '' }}">
                @include('builder.partials.image', ['asset' => $side, 'class' => 'w-full rounded-2xl shadow-2xl shadow-black/20', 'sizes' => '(min-width: 1024px) 50vw, 100vw', 'eager' => true])
            </div>
        @endif
    </div>
</div>
