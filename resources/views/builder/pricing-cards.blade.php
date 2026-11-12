@php use App\Builder\Style\StyleTokens; $center = ($design['text_align'] ?? 'left') === 'center'; @endphp
<div class="{{ StyleTokens::container($design['container'] ?? 'normal') }}">
    @include('builder.partials.heading', ['wrapperClass' => StyleTokens::align($design, $responsive).($center ? ' mx-auto max-w-3xl' : '')])
    <div class="grid gap-6 {{ StyleTokens::columns($responsive, 3, 2, 1) }}">
        @foreach (array_values($content['plans'] ?? []) as $plan)
            @php $hl = (bool) ($plan['highlighted'] ?? false); @endphp
            <div class="relative flex flex-col rounded-2xl p-7 text-left {{ $hl ? 'bg-primary text-white ring-2 ring-accent' : 'text-ink '.StyleTokens::card($design['card_style'] ?? 'elevated') }}">
                @if ($hl)<span class="absolute -top-3 left-7 rounded-full bg-accent px-3 py-1 text-xs font-bold text-primary">Rekomendasi</span>@endif
                <h3 class="text-lg font-bold">{{ $plan['name'] ?? '' }}</h3>
                @if (filled($plan['bandwidth'] ?? null))<p class="mt-1 text-sm {{ $hl ? 'text-white/80' : 'text-slate-500' }}">{{ $plan['bandwidth'] }}</p>@endif
                <p class="mt-5"><span class="text-3xl font-extrabold">{{ $plan['price'] ?? '' }}</span>@if (filled($plan['period'] ?? null))<span class="text-sm {{ $hl ? 'text-white/70' : 'text-slate-500' }}">{{ $plan['period'] }}</span>@endif</p>
                <ul class="mt-6 flex-1 space-y-2.5 text-sm">
                    @foreach (array_filter(array_map('trim', explode("\n", (string) ($plan['features'] ?? '')))) as $feature)
                        <li class="flex gap-2"><span class="{{ $hl ? 'text-accent' : 'text-secondary' }}" aria-hidden="true">✓</span>{{ $feature }}</li>
                    @endforeach
                </ul>
                @if (filled($plan['button_label'] ?? null))
                    <a href="{{ \App\Support\UrlGuard::sanitize($plan['button_url'] ?? '/kontak') }}" class="mt-7 {{ StyleTokens::button($hl ? 'accent' : 'primary') }}">{{ $plan['button_label'] }}</a>
                @endif
            </div>
        @endforeach
    </div>
    @if (filled($content['note'] ?? null))<p class="mt-6 text-center text-xs opacity-70">{{ $content['note'] }}</p>@endif
</div>
