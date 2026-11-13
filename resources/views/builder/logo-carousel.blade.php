@php use App\Builder\Style\StyleTokens; $animate = ($design['animate'] ?? true) && $partners->count() > 4 && ! $editor; @endphp
@if ($partners->isNotEmpty() || $editor)
<div class="{{ StyleTokens::container($design['container'] ?? 'normal') }}">
    @if (filled($content['heading'] ?? null))
        <p class="mb-8 text-sm font-semibold uppercase tracking-wider opacity-70 {{ StyleTokens::align($design, $responsive) }}" @if ($editor) data-inline-edit="heading" @endif>{{ $content['heading'] }}</p>
    @endif
    @if ($partners->isEmpty())
        <p class="rounded border border-dashed p-6 text-center text-sm opacity-70">Belum ada logo mitra. Kelola di Marketing → Partners.</p>
    @else
        <div class="relative overflow-hidden {{ $animate ? '[mask-image:linear-gradient(to_right,transparent,black_10%,black_90%,transparent)]' : '' }}">
            <ul class="flex items-center gap-12 {{ $animate ? 'animate-marquee w-max' : 'flex-wrap justify-center' }}">
                @foreach ($animate ? $partners->concat($partners) : $partners as $partner)
                    <li class="shrink-0" @if ($animate && $loop->index >= $partners->count()) aria-hidden="true" @endif>
                        @php $logo = $partner->logo; @endphp
                        @if ($logo)
                            @include('builder.partials.image', ['asset' => $logo, 'class' => 'h-10 w-auto object-contain transition '.(($design['grayscale'] ?? true) ? 'opacity-70 grayscale hover:opacity-100 hover:grayscale-0' : ''), 'width' => 480, 'sizes' => '160px', 'alt' => $partner->name])
                        @else
                            <span class="text-lg font-bold opacity-60">{{ $partner->name }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endif
