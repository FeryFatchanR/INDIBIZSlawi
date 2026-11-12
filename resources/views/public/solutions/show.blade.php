@extends('layouts.public')

@section('content')
    <section class="bg-gradient-to-br from-primary via-secondary to-accent py-16 text-white md:py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <span class="mb-4 grid h-12 w-12 place-items-center rounded-xl bg-white/10">{!! \App\Builder\Style\Icons::svg($solution->icon, 'h-7 w-7') !!}</span>
            <h1 class="text-4xl font-extrabold md:text-5xl">Solusi {{ $solution->name }}</h1>
            @if ($solution->short_description)<p class="mt-4 max-w-2xl text-lg opacity-90">{{ $solution->short_description }}</p>@endif
        </div>
    </section>
    <div class="mx-auto max-w-6xl space-y-16 px-4 py-16 sm:px-6 lg:px-8">
        @if ($solution->description)
            <div class="prose-brand max-w-3xl">{!! \App\Support\HtmlSanitizer::richText($solution->description) !!}</div>
        @endif
        @if ($solution->products->isNotEmpty())
            <section>
                <h2 class="section-title mb-8">Layanan yang Direkomendasikan</h2>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($solution->products as $product)
                        @include('public.products.card', ['product' => $product])
                    @endforeach
                </div>
            </section>
        @endif
        @if ($solution->caseStudies->isNotEmpty())
            <section>
                <h2 class="section-title mb-8">Studi Kasus</h2>
                <div class="grid gap-6 md:grid-cols-3">
                    @foreach ($solution->caseStudies as $case)
                        <a href="{{ $case->publicUrl() }}" class="rounded-2xl bg-white p-6 shadow-lg ring-1 ring-slate-900/5 hover:-translate-y-1 transition">
                            <h3 class="font-bold">{{ $case->title }}</h3><p class="mt-2 text-sm text-slate-600">{{ $case->summary }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
        <div class="rounded-2xl bg-primary p-8 text-center text-white md:p-12">
            <h2 class="text-2xl font-bold md:text-3xl">Diskusikan kebutuhan {{ $solution->name }} Anda</h2>
            <a href="{{ url('/kontak') }}" class="mt-6 {{ \App\Builder\Style\StyleTokens::button('accent') }}">Konsultasi Gratis</a>
        </div>
    </div>
@endsection
