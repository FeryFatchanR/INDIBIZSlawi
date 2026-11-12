@php
    use App\Builder\Style\StyleTokens;
    $bgColor = ($design['background'] ?? null) === 'custom' ? StyleTokens::hex($design['background_color'] ?? null) : null;
    $textColor = ($design['text_color'] ?? null) === 'custom' ? StyleTokens::hex($design['text_hex'] ?? null) : null;
    $style = trim(($bgColor ? "background-color: {$bgColor};" : '').($textColor ? " color: {$textColor};" : ''));
@endphp
<section
    @if (filled($design['anchor_id'] ?? null)) id="{{ $design['anchor_id'] }}" @endif
    class="{{ $classes }} {{ $editor && ! ($section['is_visible'] ?? true) ? 'is-hidden-section' : '' }}"
    @if ($style !== '') style="{{ $style }}" @endif
    @if ($editor) data-section-uuid="{{ $section['uuid'] }}" data-section-type="{{ $type->key() }}" @endif
    data-section="{{ $type->key() }}">
    {!! $inner !!}
</section>
