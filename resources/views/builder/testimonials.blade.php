@php use App\Builder\Style\StyleTokens; $center = ($design['text_align'] ?? 'left') === 'center'; @endphp
@if ($testimonials->isNotEmpty() || $editor)
<div class="{{ StyleTokens::container($design['container'] ?? 'normal') }}">
    @include('builder.partials.heading', ['wrapperClass' => StyleTokens::align($design, $responsive).($center ? ' mx-auto max-w-3xl' : '')])
    <div class="grid gap-6 {{ StyleTokens::columns($responsive, 3, 2, 1) }}">
        @forelse ($testimonials as $t)
            <figure class="flex flex-col rounded-2xl bg-white p-7 text-left text-ink shadow-lg shadow-slate-900/5 ring-1 ring-slate-900/5">
                @if (($design['show_rating'] ?? true) && $t->rating)
                    <div class="mb-3 text-amber-400" aria-label="Rating {{ $t->rating }} dari 5">{{ str_repeat('★', (int) $t->rating) }}<span class="text-slate-200">{{ str_repeat('★', 5 - (int) $t->rating) }}</span></div>
                @endif
                <blockquote class="flex-1 text-sm leading-relaxed text-slate-700">“{{ $t->quote }}”</blockquote>
                <figcaption class="mt-6 flex items-center gap-3">
                    @if ($t->photo)
                        @include('builder.partials.image', ['asset' => $t->photo, 'class' => 'h-11 w-11 rounded-full object-cover', 'width' => 480, 'sizes' => '44px', 'alt' => $t->name])
                    @else
                        <span class="grid h-11 w-11 place-items-center rounded-full bg-secondary/10 font-bold text-secondary">{{ mb_substr($t->name, 0, 1) }}</span>
                    @endif
                    <div><div class="text-sm font-bold">{{ $t->name }}</div><div class="text-xs text-slate-500">{{ collect([$t->position, $t->company])->filter()->implode(', ') }}</div></div>
                </figcaption>
            </figure>
        @empty
            <p class="col-span-full rounded border border-dashed p-6 text-center text-sm opacity-70">Belum ada testimoni yang ditampilkan. Kelola di Marketing → Testimonials.</p>
        @endforelse
    </div>
</div>
@endif
