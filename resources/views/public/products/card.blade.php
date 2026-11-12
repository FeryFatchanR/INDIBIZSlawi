<a href="{{ $product->publicUrl() }}" class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-900/5 transition hover:-translate-y-1">
    @include('builder.partials.image', ['asset' => $product->featuredImage, 'class' => 'aspect-[16/9] w-full object-cover', 'sizes' => '(min-width: 1024px) 33vw, 100vw', 'alt' => $product->name])
    <div class="flex flex-1 flex-col p-6">
        <span class="mb-4 grid h-11 w-11 place-items-center rounded-xl bg-secondary/10 text-secondary">{!! \App\Builder\Style\Icons::svg($product->icon) !!}</span>
        <h3 class="text-lg font-bold group-hover:text-secondary">{{ $product->name }}</h3>
        <p class="mt-2 flex-1 text-sm text-slate-600">{{ $product->short_description }}</p>
        <span class="mt-5 text-sm font-semibold text-secondary">Lihat detail →</span>
    </div>
</a>
