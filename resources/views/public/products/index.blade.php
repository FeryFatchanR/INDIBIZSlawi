@extends('layouts.public')

@section('content')
    <section class="bg-gradient-to-br from-primary via-secondary to-accent py-16 text-white md:py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-extrabold md:text-5xl">Layanan</h1>
            <p class="mt-4 max-w-2xl text-lg opacity-90">Internet dan solusi jaringan untuk perusahaan, instansi, dan UMKM.</p>
        </div>
    </section>
    <div class="mx-auto max-w-6xl space-y-16 px-4 py-16 sm:px-6 lg:px-8">
        @foreach ($categories as $category)
            <section aria-labelledby="cat-{{ $category->id }}">
                <h2 id="cat-{{ $category->id }}" class="section-title">{{ $category->name }}</h2>
                @if ($category->description)<p class="section-subtitle">{{ $category->description }}</p>@endif
                <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($category->products as $product)
                        @include('public.products.card', ['product' => $product])
                    @endforeach
                </div>
            </section>
        @endforeach
        @if ($uncategorized->isNotEmpty())
            <section>
                <h2 class="section-title">Layanan Lainnya</h2>
                <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($uncategorized as $product)
                        @include('public.products.card', ['product' => $product])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
