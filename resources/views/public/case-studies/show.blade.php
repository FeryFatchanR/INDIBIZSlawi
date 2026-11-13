@extends('layouts.public')

@section('content')
    <article>
        <header class="bg-surface py-16">
            <div class="mx-auto max-w-3xl px-4 sm:px-6">
                @if ($case->industrySolution)<a href="{{ $case->industrySolution->publicUrl() }}" class="text-sm font-semibold uppercase tracking-wider text-secondary">{{ $case->industrySolution->name }}</a>@endif
                <h1 class="mt-3 text-4xl font-extrabold">{{ $case->title }}</h1>
                <p class="mt-4 text-slate-600">Klien: <strong>{{ $case->client_name }}</strong></p>
                @if ($case->summary)<p class="mt-4 text-lg text-slate-700">{{ $case->summary }}</p>@endif
            </div>
        </header>
        <div class="mx-auto max-w-3xl space-y-10 px-4 py-16 sm:px-6">
            @include('builder.partials.image', ['asset' => $case->image, 'class' => 'w-full rounded-2xl', 'alt' => $case->title, 'eager' => true])
            @foreach (['challenge' => 'Tantangan', 'solution' => 'Solusi', 'result' => 'Hasil'] as $field => $label)
                @if ($case->{$field})
                    <section><h2 class="section-title mb-4">{{ $label }}</h2><div class="prose-brand">{!! \App\Support\HtmlSanitizer::richText($case->{$field}) !!}</div></section>
                @endif
            @endforeach
        </div>
    </article>
@endsection
