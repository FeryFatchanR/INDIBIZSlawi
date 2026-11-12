@php
    use App\Builder\Style\StyleTokens;
    $embed = \App\Builder\Sections\MapSection::resolveEmbedUrl($content, $settings->get('contact.maps_embed_url'));
    $height = ['sm' => 'h-64', 'md' => 'h-96', 'lg' => 'h-[32rem]'][$design['map_height'] ?? 'md'] ?? 'h-96';
@endphp
<div class="{{ StyleTokens::container($design['container'] ?? 'normal') }}">
    @if (filled($content['heading'] ?? null))<h2 class="section-title mb-8 {{ StyleTokens::align($design, $responsive) }}">{{ $content['heading'] }}</h2>@endif
    @if ($embed)
        <iframe src="{{ $embed }}" title="{{ $content['heading'] ?? 'Peta lokasi' }}" class="{{ $height }} w-full rounded-2xl border-0 {{ $editor ? 'pointer-events-none' : '' }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
    @elseif ($editor)
        <div class="{{ $height }} grid place-items-center rounded-2xl border-2 border-dashed border-slate-300 text-sm text-slate-500">Peta belum diatur (Website Settings → Kontak atau panel properti)</div>
    @endif
</div>
