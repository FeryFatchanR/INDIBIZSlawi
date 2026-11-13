@php
    use App\Builder\Style\StyleTokens;
    $contact = $settings->group('contact');
    $showInfo = (bool) ($content['show_contact_info'] ?? true);
@endphp
<div class="{{ StyleTokens::container($design['container'] ?? 'normal') }}">
    <div class="grid gap-10 {{ $showInfo ? 'lg:grid-cols-5' : '' }}">
        <div class="{{ $showInfo ? 'lg:col-span-2' : 'mx-auto max-w-2xl text-center' }}">
            @if (filled($content['heading'] ?? null))<h2 class="section-title" @if ($editor) data-inline-edit="heading" @endif>{{ $content['heading'] }}</h2>@endif
            @if (filled($content['description'] ?? null))<p class="section-subtitle">{{ $content['description'] }}</p>@endif
            @if ($showInfo)
                <ul class="mt-8 space-y-4 text-sm">
                    @if (filled($contact['phone'] ?? null))<li class="flex gap-3">{!! \App\Builder\Style\Icons::svg('phone', 'h-5 w-5 text-secondary') !!}<span>{{ $contact['phone'] }}</span></li>@endif
                    @if (filled($contact['email'] ?? null))<li class="flex gap-3">{!! \App\Builder\Style\Icons::svg('chat-bubble-left-right', 'h-5 w-5 text-secondary') !!}<span>{{ $contact['email'] }}</span></li>@endif
                    @if (filled($contact['address'] ?? null))<li class="flex gap-3">{!! \App\Builder\Style\Icons::svg('map-pin', 'h-5 w-5 text-secondary') !!}<span>{{ $contact['address'] }}</span></li>@endif
                    @if (filled($contact['business_hours'] ?? null))<li class="flex gap-3">{!! \App\Builder\Style\Icons::svg('clock', 'h-5 w-5 text-secondary') !!}<span>{{ $contact['business_hours'] }}</span></li>@endif
                </ul>
                @if (\App\Support\WhatsApp::enabled())
                    <a href="{{ route('whatsapp') }}" target="_blank" rel="noopener noreferrer" class="mt-8 inline-flex items-center gap-2 rounded-lg bg-[#25D366] px-5 py-3 text-sm font-semibold text-white hover:opacity-90">Chat via WhatsApp</a>
                @endif
            @endif
        </div>
        <div class="{{ $showInfo ? 'lg:col-span-3' : 'mx-auto w-full max-w-2xl' }} rounded-2xl bg-white p-6 text-left text-ink shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 sm:p-8">
            @include('public.partials.lead-form', [
                'formType' => $content['form_type'] ?? 'contact',
                'submitLabel' => $content['submit_label'] ?? 'Kirim',
                'showRfqFields' => (bool) ($content['show_rfq_fields'] ?? true),
                'formId' => substr($section['uuid'], 0, 8),
            ])
        </div>
    </div>
</div>
