@php use App\Builder\Style\StyleTokens; $center = ($design['text_align'] ?? 'left') === 'center'; @endphp
<div class="{{ StyleTokens::container($design['container'] ?? 'narrow') }}">
    @include('builder.partials.heading', ['wrapperClass' => StyleTokens::align($design, $responsive).($center ? ' mx-auto max-w-3xl' : '')])
    <div class="divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white text-left text-ink">
        @forelse ($faqs as $i => $faq)
            <div x-data="{ open: {{ $i === 0 ? 'true' : 'false' }} }">
                <h3>
                    <button type="button" @click="open = !open" :aria-expanded="open" class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left font-semibold">
                        <span>{{ $faq->question }}</span>
                        <span class="text-xl text-secondary transition" :class="open && 'rotate-45'" aria-hidden="true">+</span>
                    </button>
                </h3>
                <div x-show="open" {{ $i === 0 ? '' : 'x-cloak' }} class="px-6 pb-5 text-sm leading-relaxed text-slate-600">{!! nl2br(e($faq->answer)) !!}</div>
            </div>
        @empty
            <p class="p-6 text-center text-sm opacity-70">Belum ada FAQ.</p>
        @endforelse
    </div>
</div>
