@php use App\Builder\Style\StyleTokens; use App\Builder\Style\Icons; $center = ($design['text_align'] ?? 'left') === 'center'; @endphp
<div class="{{ StyleTokens::container($design['container'] ?? 'normal') }}">
    @include('builder.partials.heading', ['wrapperClass' => StyleTokens::align($design, $responsive).($center ? ' mx-auto max-w-3xl' : '')])
    <div class="grid gap-5 {{ StyleTokens::columns($responsive, 4, 2, 1) }}">
        @foreach ($solutions as $solution)
            <a href="{{ $solution->publicUrl() }}" class="group flex flex-col rounded-2xl border border-slate-200 bg-white p-6 text-left text-ink transition hover:-translate-y-1 hover:border-secondary hover:shadow-lg">
                <span class="mb-4 grid h-11 w-11 place-items-center rounded-xl bg-accent/10 text-secondary">{!! Icons::svg($solution->icon) !!}</span>
                <h3 class="font-bold group-hover:text-secondary">{{ $solution->name }}</h3>
                @if ($solution->short_description)<p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $solution->short_description }}</p>@endif
            </a>
        @endforeach
    </div>
</div>
