@php use App\Builder\Style\StyleTokens; $center = ($design['text_align'] ?? 'center') === 'center'; @endphp
<div class="{{ StyleTokens::container($design['container'] ?? 'normal') }} {{ StyleTokens::align($design, $responsive) }}">
    <div class="{{ $center ? 'mx-auto max-w-3xl' : 'max-w-3xl' }}">
        <h2 class="section-title" @if ($editor) data-inline-edit="heading" @endif>{{ $content['heading'] ?? '' }}</h2>
        @if (filled($content['description'] ?? null))<p class="section-subtitle" @if ($editor) data-inline-edit="description" @endif>{{ $content['description'] }}</p>@endif
        <div class="mt-8 flex flex-wrap gap-3 {{ $center ? 'justify-center' : '' }}">
            @include('builder.partials.button', ['button' => $content['primary_button'] ?? [], 'default' => 'accent'])
            @if (($content['show_whatsapp'] ?? false) && \App\Support\WhatsApp::enabled())
                <a href="{{ route('whatsapp') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#25D366] px-5 py-3 text-sm font-semibold text-white hover:opacity-90">{{ $content['whatsapp_label'] ?? 'Chat WhatsApp' }}</a>
            @endif
        </div>
    </div>
</div>
