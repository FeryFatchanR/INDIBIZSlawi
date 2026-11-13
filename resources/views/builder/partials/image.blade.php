{{-- Gambar responsif dari Media Library (srcset WebP + lazy loading). --}}
@php $asset = $asset ?? null; @endphp
@if ($asset && $asset->isImage())
    <img src="{{ $asset->url($width ?? 1440) }}" @if ($asset->srcset()) srcset="{{ $asset->srcset() }}" sizes="{{ $sizes ?? '100vw' }}" @endif
        alt="{{ $alt ?? $asset->alt ?? '' }}" @if ($asset->width) width="{{ $asset->width }}" height="{{ $asset->height }}" @endif
        loading="{{ ($eager ?? false) ? 'eager' : 'lazy' }}" decoding="async" class="{{ $class ?? '' }}">
@endif
