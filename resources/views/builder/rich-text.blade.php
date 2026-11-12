@php use App\Builder\Style\StyleTokens; use App\Support\HtmlSanitizer; @endphp
<div class="{{ StyleTokens::container($design['container'] ?? 'narrow') }} {{ StyleTokens::align($design, $responsive) }}">
    @if (filled($content['heading'] ?? null))
        <h2 class="section-title mb-6" @if ($editor) data-inline-edit="heading" @endif>{{ $content['heading'] }}</h2>
    @endif
    {{-- Disanitasi saat simpan; disanitasi ulang saat render sebagai pertahanan berlapis. --}}
    <div class="prose-brand {{ $isDark ? 'prose-invert-brand' : '' }}">{!! HtmlSanitizer::richText($content['body'] ?? '') !!}</div>
</div>
