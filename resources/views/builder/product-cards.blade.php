@php
    use App\Builder\Style\StyleTokens;
    use App\Builder\Style\Icons;
    $target = $content['button_target'] ?? 'product_page';
    $center = ($design['text_align'] ?? 'left') === 'center';
@endphp
<div class="{{ StyleTokens::container($design['container'] ?? 'normal') }}">
    @include('builder.partials.heading', ['wrapperClass' => StyleTokens::align($design, $responsive).($center ? ' mx-auto max-w-3xl' : '')])
    <div class="grid gap-6 {{ StyleTokens::columns($responsive, 3, 2, 1) }}">
        @forelse ($products as $product)
            @php
                $url = match ($target) {
                    'whatsapp' => \App\Support\WhatsApp::link($product),
                    'contact' => url('/kontak').'?produk='.urlencode($product->slug),
                    default => $product->publicUrl(),
                };
            @endphp
            <article class="flex flex-col overflow-hidden rounded-2xl text-left text-ink transition hover:-translate-y-1 {{ StyleTokens::card($design['card_style'] ?? 'elevated') }}">
                @if (($design['show_image'] ?? true) && $product->featuredImage)
                    @include('builder.partials.image', ['asset' => $product->featuredImage, 'class' => 'aspect-[16/9] w-full object-cover', 'sizes' => '(min-width: 1024px) 33vw, 100vw', 'alt' => $product->name])
                @endif
                <div class="flex flex-1 flex-col p-6">
                    <div class="mb-4 grid h-11 w-11 place-items-center rounded-xl bg-secondary/10 text-secondary">{!! Icons::svg($product->icon, 'h-6 w-6') !!}</div>
                    <h3 class="text-lg font-bold">{{ $product->name }}</h3>
                    @if ($product->short_description)<p class="mt-2 flex-1 text-sm leading-relaxed text-slate-600">{{ $product->short_description }}</p>@endif
                    @if (($design['show_price'] ?? false) && $product->show_price && $product->price_label)
                        <p class="mt-4 text-base font-bold text-primary">{{ $product->price_label }}</p>
                    @endif
                    @if (filled($content['button_label'] ?? null))
                        <a href="{{ $url }}" class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-secondary hover:gap-2 hover:underline" @if ($target === 'whatsapp') target="_blank" rel="noopener noreferrer" @endif>
                            {{ $content['button_label'] }} <span aria-hidden="true">→</span>
                        </a>
                    @endif
                </div>
            </article>
        @empty
            @if ($editor)<p class="col-span-full rounded border border-dashed p-6 text-center text-sm opacity-70">Belum ada produk published untuk sumber ini.</p>@endif
        @endforelse
    </div>
</div>
