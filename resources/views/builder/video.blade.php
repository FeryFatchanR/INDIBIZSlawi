@php
    use App\Builder\Style\StyleTokens;
    $provider = $content['provider'] ?? 'youtube';
    $videoId = preg_match('/^[A-Za-z0-9_\-]{1,32}$/', (string) ($content['video_id'] ?? '')) ? $content['video_id'] : null;
    $file = $provider === 'upload' ? $media->get($content['media_id'] ?? null) : null;
    $embed = match (true) {
        $provider === 'youtube' && $videoId !== null => 'https://www.youtube-nocookie.com/embed/'.$videoId,
        $provider === 'vimeo' && $videoId !== null && ctype_digit($videoId) => 'https://player.vimeo.com/video/'.$videoId,
        default => null,
    };
@endphp
<div class="{{ StyleTokens::container($design['container'] ?? 'narrow') }}">
    @if (filled($content['heading'] ?? null))
        <h2 class="section-title mb-8 {{ StyleTokens::align($design, $responsive) }}">{{ $content['heading'] }}</h2>
    @endif
    <div class="aspect-video overflow-hidden rounded-2xl bg-slate-900 shadow-xl">
        @if ($embed)
            <iframe src="{{ $embed }}" title="{{ $content['heading'] ?? 'Video' }}" class="h-full w-full {{ $editor ? 'pointer-events-none' : '' }}" loading="lazy"
                allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
        @elseif ($file)
            <video src="{{ $file->url() }}" controls preload="metadata" class="h-full w-full"></video>
        @elseif ($editor)
            <div class="grid h-full place-items-center text-sm text-white/70">Atur sumber video di panel properti</div>
        @endif
    </div>
    @if (filled($content['caption'] ?? null))<p class="mt-3 text-center text-sm opacity-70">{{ $content['caption'] }}</p>@endif
</div>
