@extends('layouts.public')

@section('content')
    @php $waUrl = \App\Support\WhatsApp::enabled() ? \App\Support\WhatsApp::link($product) : null; @endphp
    <section class="bg-gradient-to-br from-primary via-secondary to-accent text-white">
        <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-16 sm:px-6 md:py-20 lg:grid-cols-2 lg:px-8">
            <div>
                @if ($product->categories->isNotEmpty())
                    <span class="mb-4 inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider ring-1 ring-white/20">{{ $product->categories->pluck('name')->implode(' · ') }}</span>
                @endif
                <h1 class="text-4xl font-extrabold md:text-5xl">{{ $product->name }}</h1>
                @if ($product->short_description)<p class="mt-5 text-lg opacity-90">{{ $product->short_description }}</p>@endif
                <dl class="mt-8 grid gap-4 sm:grid-cols-2">
                    @if ($product->bandwidth_label)<div class="rounded-xl bg-white/10 p-4"><dt class="text-xs uppercase tracking-wider opacity-70">Bandwidth</dt><dd class="mt-1 font-semibold">{{ $product->bandwidth_label }}</dd></div>@endif
                    @if ($product->sla)<div class="rounded-xl bg-white/10 p-4"><dt class="text-xs uppercase tracking-wider opacity-70">SLA</dt><dd class="mt-1 font-semibold">{{ $product->sla }}</dd></div>@endif
                    @if ($product->show_price && $product->price_label)<div class="rounded-xl bg-white/10 p-4"><dt class="text-xs uppercase tracking-wider opacity-70">Harga</dt><dd class="mt-1 font-semibold">{{ $product->price_label }}</dd></div>@endif
                </dl>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="#lead-form" class="{{ \App\Builder\Style\StyleTokens::button('accent') }}">{{ $product->cta_label ?: 'Minta Penawaran' }}</a>
                    @if ($waUrl)
                        <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#25D366] px-5 py-3 text-sm font-semibold text-white hover:opacity-90">Tanya via WhatsApp</a>
                    @endif
                </div>
            </div>
            <div>
                @include('builder.partials.image', ['asset' => $product->featuredImage, 'class' => 'w-full rounded-2xl shadow-2xl', 'sizes' => '(min-width: 1024px) 50vw, 100vw', 'alt' => $product->name, 'eager' => true])
            </div>
        </div>
    </section>

    <div class="mx-auto grid max-w-6xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-3 lg:px-8">
        <div class="space-y-12 lg:col-span-2">
            @if ($product->description)
                <section>
                    <h2 class="section-title mb-6">Deskripsi Layanan</h2>
                    <div class="prose-brand">{!! \App\Support\HtmlSanitizer::richText($product->description) !!}</div>
                </section>
            @endif
            @if (! empty($product->benefits))
                <section>
                    <h2 class="section-title mb-6">Manfaat</h2>
                    <ul class="grid gap-4 sm:grid-cols-2">
                        @foreach ($product->benefits as $benefit)
                            <li class="flex gap-3 rounded-xl border border-slate-200 p-4"><span class="text-secondary" aria-hidden="true">✓</span><span>{{ is_array($benefit) ? ($benefit['value'] ?? '') : $benefit }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif
            @if ($product->specifications->isNotEmpty())
                <section>
                    <h2 class="section-title mb-6">Spesifikasi</h2>
                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <table class="w-full text-left text-sm">
                            <tbody class="divide-y divide-slate-200">
                                @foreach ($product->specifications as $spec)
                                    <tr class="even:bg-surface"><th scope="row" class="w-1/3 px-4 py-3 font-semibold">{{ $spec->label }}</th><td class="px-4 py-3">{{ $spec->value }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
            @if ($product->galleries->isNotEmpty())
                <section>
                    <h2 class="section-title mb-6">Galeri</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($product->galleries as $item)
                            <figure>
                                @include('builder.partials.image', ['asset' => $item->media, 'class' => 'aspect-[4/3] w-full rounded-xl object-cover', 'sizes' => '(min-width: 640px) 50vw, 100vw', 'alt' => $item->caption ?: $product->name])
                                @if ($item->caption)<figcaption class="mt-2 text-sm text-slate-500">{{ $item->caption }}</figcaption>@endif
                            </figure>
                        @endforeach
                    </div>
                </section>
            @endif
            @if ($product->faqs->isNotEmpty())
                <section>
                    <h2 class="section-title mb-6">FAQ</h2>
                    <div class="divide-y divide-slate-200 rounded-2xl border border-slate-200">
                        @foreach ($product->faqs as $i => $faq)
                            <div x-data="{ open: {{ $i === 0 ? 'true' : 'false' }} }">
                                <h3><button type="button" @click="open = !open" :aria-expanded="open" class="flex w-full justify-between gap-4 px-6 py-5 text-left font-semibold">{{ $faq->question }}<span class="text-secondary" aria-hidden="true" :class="open && 'rotate-45'">+</span></button></h3>
                                <div x-show="open" {{ $i === 0 ? '' : 'x-cloak' }} class="px-6 pb-5 text-sm text-slate-600">{!! nl2br(e($faq->answer)) !!}</div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
        <aside class="space-y-6">
            @if (! empty($product->target_customers))
                <div class="rounded-2xl bg-surface p-6">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-secondary">Cocok untuk</h2>
                    <ul class="mt-4 flex flex-wrap gap-2">
                        @foreach ($product->target_customers as $target)
                            <li class="rounded-full bg-white px-3 py-1 text-sm ring-1 ring-slate-200">{{ is_array($target) ? ($target['value'] ?? '') : $target }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($product->industrySolutions->isNotEmpty())
                <div class="rounded-2xl bg-surface p-6">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-secondary">Solusi industri</h2>
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach ($product->industrySolutions->where('is_visible', true) as $solution)
                            <li><a href="{{ $solution->publicUrl() }}" class="hover:text-secondary hover:underline">{{ $solution->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>

    <section class="bg-surface py-16">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="section-title text-center">Minta Penawaran {{ $product->name }}</h2>
            <p class="section-subtitle text-center">Isi formulir berikut, tim kami akan menghubungi Anda.</p>
            <div class="mt-8 rounded-2xl bg-white p-6 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 sm:p-8">
                @include('public.partials.lead-form', [
                    'formType' => 'rfq',
                    'submitLabel' => 'Kirim Permintaan Penawaran',
                    'products' => \App\Models\Product::query()->published()->get(['id', 'name', 'slug']),
                    'industries' => \App\Models\IndustrySolution::query()->visible()->get(['id', 'name']),
                    'selectedProductId' => $product->id,
                    'formId' => 'product',
                ])
            </div>
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
            <h2 class="section-title mb-8">Layanan Terkait</h2>
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($related as $item)
                    @include('public.products.card', ['product' => $item])
                @endforeach
            </div>
        </section>
    @endif
@endsection
