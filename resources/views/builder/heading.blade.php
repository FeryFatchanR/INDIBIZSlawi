@php use App\Builder\Style\StyleTokens; @endphp
<div class="{{ StyleTokens::container($design['container'] ?? 'normal') }} {{ StyleTokens::align($design, $responsive) }}">
    @include('builder.partials.heading', ['level' => $content['level'] ?? 'h2', 'wrapperClass' => '!mb-0 '.(($design['text_align'] ?? 'left') === 'center' ? 'mx-auto max-w-3xl' : '')])
</div>
