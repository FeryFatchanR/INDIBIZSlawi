@php use App\Builder\Style\StyleTokens; $center = ($design['text_align'] ?? 'left') === 'center'; @endphp
@if ($cases->isNotEmpty() || $editor)
<div class="{{ StyleTokens::container($design['container'] ?? 'normal') }}">
    @include('builder.partials.heading', ['wrapperClass' => StyleTokens::align($design, $responsive).($center ? ' mx-auto max-w-3xl' : '')])
    <div class="grid gap-6 {{ StyleTokens::columns($responsive, 3, 2, 1) }}">
        @forelse ($cases as $case)
            <a href="{{ $case->publicUrl() }}" class="group overflow-hidden rounded-2xl bg-white text-left text-ink shadow-lg shadow-slate-900/5 ring-1 ring-slate-900/5 transition hover:-translate-y-1">
                @include('builder.partials.image', ['asset' => $case->image, 'class' => 'aspect-[16/9] w-full object-cover', 'sizes' => '(min-width: 1024px) 33vw, 100vw', 'alt' => $case->title])
                <div class="p-6">
                    @if ($case->industrySolution)<span class="text-xs font-semibold uppercase tracking-wider text-secondary">{{ $case->industrySolution->name }}</span>@endif
                    <h3 class="mt-2 text-lg font-bold group-hover:text-secondary">{{ $case->title }}</h3>
                    @if ($case->summary)<p class="mt-2 text-sm text-slate-600">{{ $case->summary }}</p>@endif
                </div>
            </a>
        @empty
            <p class="col-span-full rounded border border-dashed p-6 text-center text-sm opacity-70">Belum ada studi kasus yang ditampilkan. Kelola di Marketing → Case Studies.</p>
        @endforelse
    </div>
</div>
@endif
