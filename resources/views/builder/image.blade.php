@php
    use App\Builder\Style\StyleTokens;
    $asset = $media->get($content['image_id'] ?? null);
    $link = filled($content['link_url'] ?? null) ? \App\Support\UrlGuard::sanitize($content['link_url']) : null;
    $imgClass = 'w-full '.(($design['rounded'] ?? true) ? 'rounded-2xl ' : '').(($design['shadow'] ?? false) ? 'shadow-xl' : '');
@endphp
<figure class="{{ StyleTokens::container($design['container'] ?? 'normal') }} {{ StyleTokens::align($design, $responsive) }}">
    @if ($asset)
        @if ($link)<a href="{{ $link }}">@endif
        @include('builder.partials.image', ['asset' => $asset, 'class' => $imgClass, 'alt' => $content['alt'] ?? null])
        @if ($link)</a>@endif
    @elseif ($editor)
        <div class="grid h-48 place-items-center rounded-2xl border-2 border-dashed border-slate-300 text-sm text-slate-500">Pilih gambar di panel properti</div>
    @endif
    @if (filled($content['caption'] ?? null))
        <figcaption class="mt-3 text-sm opacity-70">{{ $content['caption'] }}</figcaption>
    @endif
</figure>
