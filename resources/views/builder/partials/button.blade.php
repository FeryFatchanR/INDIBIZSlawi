@php $btn = $button ?? []; @endphp
@if (filled($btn['label'] ?? null))
    @php
        $url = \App\Support\UrlGuard::sanitize($btn['url'] ?? null);
        $newTab = (bool) ($btn['new_tab'] ?? false);
    @endphp
    <a href="{{ $url }}" class="{{ \App\Builder\Style\StyleTokens::button($btn['style'] ?? ($default ?? 'primary')) }}"
        @if ($newTab) target="_blank" rel="noopener noreferrer" @endif>{{ $btn['label'] }}</a>
@endif
