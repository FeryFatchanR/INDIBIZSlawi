@php use App\Builder\Style\StyleTokens; @endphp
<div class="{{ StyleTokens::container($design['container'] ?? 'normal') }}">
    @if (filled($content['heading'] ?? null))
        <h2 class="section-title mb-8 {{ StyleTokens::align($design, $responsive) }}" @if ($editor) data-inline-edit="heading" @endif>{{ $content['heading'] }}</h2>
    @endif
    <div class="grid gap-4 {{ StyleTokens::columns($responsive, 3, 2, 1) }}">
        @foreach (array_values($content['images'] ?? []) as $item)
            @php $asset = $media->get($item['image_id'] ?? null); @endphp
            @if ($asset)
                <figure>
                    @include('builder.partials.image', ['asset' => $asset, 'class' => 'aspect-[4/3] w-full rounded-xl object-cover', 'sizes' => '(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw', 'alt' => $item['caption'] ?? null])
                    @if (filled($item['caption'] ?? null))<figcaption class="mt-2 text-sm opacity-70">{{ $item['caption'] }}</figcaption>@endif
                </figure>
            @endif
        @endforeach
    </div>
    @if ($editor && empty($content['images']))
        <div class="grid h-40 place-items-center rounded-2xl border-2 border-dashed border-slate-300 text-sm text-slate-500">Tambahkan gambar di panel properti</div>
    @endif
</div>
