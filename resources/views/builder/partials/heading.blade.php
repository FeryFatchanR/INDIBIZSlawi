{{-- Judul section standar: eyebrow + heading + subheading (semua di-escape). --}}
@php $tag = in_array($level ?? 'h2', ['h1', 'h2', 'h3'], true) ? ($level ?? 'h2') : 'h2'; @endphp
@if (filled($content['eyebrow'] ?? null) || filled($content['heading'] ?? null) || filled($content['subheading'] ?? null))
    <div class="mb-10 {{ $wrapperClass ?? '' }}">
        @if (filled($content['eyebrow'] ?? null))
            <span class="section-eyebrow" @if ($editor) data-inline-edit="eyebrow" @endif>{{ $content['eyebrow'] }}</span>
        @endif
        @if (filled($content['heading'] ?? null))
            <{{ $tag }} class="section-title" @if ($editor) data-inline-edit="heading" @endif>{{ $content['heading'] }}</{{ $tag }}>
        @endif
        @if (filled($content['subheading'] ?? null))
            <p class="section-subtitle" @if ($editor) data-inline-edit="subheading" @endif>{{ $content['subheading'] }}</p>
        @endif
    </div>
@endif
