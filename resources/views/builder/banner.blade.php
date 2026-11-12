@php use App\Builder\Style\StyleTokens; @endphp
<div class="{{ StyleTokens::container($design['container'] ?? 'normal') }}">
    @if (($content['source'] ?? 'manual') === 'banners')
        <div class="grid gap-6">
            @forelse ($banners as $banner)
                <div class="relative overflow-hidden rounded-2xl bg-primary text-white">
                    @include('builder.partials.image', ['asset' => $banner->image, 'class' => 'absolute inset-0 h-full w-full object-cover opacity-40', 'alt' => ''])
                    <div class="relative flex flex-col gap-4 p-8 md:flex-row md:items-center md:justify-between md:p-10">
                        <div>
                            <h2 class="text-2xl font-bold md:text-3xl">{{ $banner->title }}</h2>
                            @if ($banner->subtitle)<p class="mt-2 opacity-90">{{ $banner->subtitle }}</p>@endif
                        </div>
                        @include('builder.partials.button', ['button' => ['label' => $banner->button_label, 'url' => $banner->button_url, 'style' => 'light']])
                    </div>
                </div>
            @empty
                @if ($editor)<p class="rounded border border-dashed p-6 text-center text-sm opacity-70">Belum ada banner aktif untuk placement ini.</p>@endif
            @endforelse
        </div>
    @else
        @php $image = $media->get($content['image_id'] ?? null); @endphp
        <div class="relative overflow-hidden rounded-2xl">
            @if ($image)
                @include('builder.partials.image', ['asset' => $image, 'class' => 'absolute inset-0 h-full w-full object-cover opacity-30', 'alt' => ''])
            @endif
            <div class="relative flex flex-col gap-4 px-2 py-4 md:flex-row md:items-center md:justify-between {{ StyleTokens::align($design, $responsive) }}">
                <div>
                    @if (filled($content['title'] ?? null))<h2 class="text-2xl font-bold md:text-3xl" @if ($editor) data-inline-edit="title" @endif>{{ $content['title'] }}</h2>@endif
                    @if (filled($content['subtitle'] ?? null))<p class="mt-2 opacity-90">{{ $content['subtitle'] }}</p>@endif
                </div>
                @include('builder.partials.button', ['button' => $content['button'] ?? [], 'default' => 'light'])
            </div>
        </div>
    @endif
</div>
