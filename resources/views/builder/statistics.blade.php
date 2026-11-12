@php use App\Builder\Style\StyleTokens; @endphp
<div class="{{ StyleTokens::container($design['container'] ?? 'normal') }} {{ StyleTokens::align($design, $responsive) }}">
    @if (filled($content['heading'] ?? null))<h2 class="section-title mb-10">{{ $content['heading'] }}</h2>@endif
    <dl class="grid gap-8 {{ StyleTokens::columns($responsive, 3, 3, 1) }}">
        @foreach ($stats as $stat)
            <div>
                <dt class="sr-only">{{ $stat->label }}</dt>
                <dd class="font-display text-4xl font-extrabold md:text-5xl">{{ $stat->value }}<span class="{{ $isDark ? 'text-accent' : 'text-secondary' }}">{{ $stat->suffix ?? '' }}</span></dd>
                <dd class="mt-2 text-sm font-medium opacity-80">{{ $stat->label }}</dd>
            </div>
        @endforeach
    </dl>
</div>
