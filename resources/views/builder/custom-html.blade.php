@php use App\Builder\Style\StyleTokens; @endphp
<div class="{{ StyleTokens::container($design['container'] ?? 'normal') }}">
    {{-- Sanitasi ulang saat render: tidak ada script/iframe/style/event handler yang lolos. --}}
    {!! \App\Support\HtmlSanitizer::customHtml($content['html'] ?? '') !!}
</div>
