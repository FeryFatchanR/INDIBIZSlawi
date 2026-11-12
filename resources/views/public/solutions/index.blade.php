@extends('layouts.public')

@section('content')
    <section class="bg-gradient-to-br from-primary via-secondary to-accent py-16 text-white md:py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-extrabold md:text-5xl">Solusi Industri</h1>
            <p class="mt-4 max-w-2xl text-lg opacity-90">Solusi konektivitas yang disesuaikan untuk kebutuhan setiap sektor.</p>
        </div>
    </section>
    <div class="mx-auto grid max-w-6xl gap-6 px-4 py-16 sm:px-6 md:grid-cols-2 lg:grid-cols-4 lg:px-8">
        @foreach ($solutions as $solution)
            <a href="{{ $solution->publicUrl() }}" class="group rounded-2xl border border-slate-200 bg-white p-6 transition hover:-translate-y-1 hover:border-secondary hover:shadow-lg">
                <span class="mb-4 grid h-11 w-11 place-items-center rounded-xl bg-accent/10 text-secondary">{!! \App\Builder\Style\Icons::svg($solution->icon) !!}</span>
                <h2 class="font-bold group-hover:text-secondary">{{ $solution->name }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ $solution->short_description }}</p>
            </a>
        @endforeach
    </div>
@endsection
