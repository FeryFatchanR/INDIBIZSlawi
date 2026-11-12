@php
    use App\Builder\Style\StyleTokens;
    use App\Builder\Style\Icons;
    $layout = $design['layout'] ?? 'cards';
    $center = ($design['text_align'] ?? 'left') === 'center';
@endphp
<div class="{{ StyleTokens::container($design['container'] ?? 'normal') }}">
    @include('builder.partials.heading', ['wrapperClass' => StyleTokens::align($design, $responsive).($center ? ' mx-auto max-w-3xl' : '')])
    <div class="grid gap-6 {{ StyleTokens::columns($responsive, 3, 2, 1) }}">
        @foreach (array_values($content['items'] ?? []) as $item)
            @if ($layout === 'list')
                <div class="flex gap-4 text-left">
                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl {{ $isDark ? 'bg-white/10 text-accent' : 'bg-secondary/10 text-secondary' }}">{!! Icons::svg($item['icon'] ?? null) !!}</div>
                    <div><h3 class="font-bold">{{ $item['title'] ?? '' }}</h3>@if (filled($item['description'] ?? null))<p class="mt-1 text-sm leading-relaxed opacity-80">{{ $item['description'] }}</p>@endif</div>
                </div>
            @else
                <div class="rounded-2xl p-6 {{ $center ? 'text-center' : 'text-left' }} {{ $layout === 'minimal' ? '' : ($isDark ? 'bg-white/5 ring-1 ring-white/10' : 'text-ink '.StyleTokens::card($design['card_style'] ?? 'bordered')) }}">
                    <div class="mb-4 inline-grid h-12 w-12 place-items-center rounded-xl {{ $isDark ? 'bg-white/10 text-accent' : 'bg-gradient-to-br from-secondary to-accent text-white' }}">{!! Icons::svg($item['icon'] ?? null) !!}</div>
                    <h3 class="text-lg font-bold">{{ $item['title'] ?? '' }}</h3>
                    @if (filled($item['description'] ?? null))<p class="mt-2 text-sm leading-relaxed {{ $isDark ? 'opacity-80' : 'text-slate-600' }}">{{ $item['description'] }}</p>@endif
                </div>
            @endif
        @endforeach
    </div>
</div>
