@props(['seo'])
@php
    $siteName = app(\App\Support\SettingsRepository::class)->get('general.site_name');
    $organization = [
        '@context' => 'https://schema.org',
        '@type' => in_array(app(\App\Support\SettingsRepository::class)->get('seo.organization_type'), ['Organization', 'LocalBusiness', 'Corporation'], true) ? app(\App\Support\SettingsRepository::class)->get('seo.organization_type') : 'Organization',
        'name' => $siteName,
        'url' => url('/'),
    ];
@endphp
<title>{{ $seo->title }}</title>
@if ($seo->description)
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($seo->description, 300) }}">
@endif
@if ($seo->noindex)
    <meta name="robots" content="noindex, nofollow">
@endif
<link rel="canonical" href="{{ $seo->canonical }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="{{ $seo->type }}">
<meta property="og:title" content="{{ $seo->title }}">
@if ($seo->description)<meta property="og:description" content="{{ \Illuminate\Support\Str::limit($seo->description, 300) }}">@endif
<meta property="og:url" content="{{ $seo->canonical }}">
<meta property="og:locale" content="id_ID">
@if ($seo->image)<meta property="og:image" content="{{ $seo->image }}">@endif
<meta name="twitter:card" content="{{ $seo->image ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo->title }}">
@if ($seo->description)<meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit($seo->description, 200) }}">@endif
@if ($seo->image)<meta name="twitter:image" content="{{ $seo->image }}">@endif
{{-- JSON_HEX_TAG mencegah "</script>" di data merusak konteks script. --}}
<script type="application/ld+json">{!! json_encode($organization, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@foreach ($seo->jsonLd as $data)
    <script type="application/ld+json">{!! json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endforeach
