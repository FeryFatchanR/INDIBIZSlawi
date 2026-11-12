@php
    /** @var \App\Support\SettingsRepository $settings */
    $brand = $settings->group('branding');
    $hex = fn ($key, $fallback) => \App\Builder\Style\StyleTokens::hex($brand[$key] ?? null) ?? $fallback;
    $fonts = config('cms.fonts');
    $fontHeading = array_key_exists($brand['font_heading'] ?? '', $fonts) ? $brand['font_heading'] : 'Plus Jakarta Sans';
    $fontBody = array_key_exists($brand['font_body'] ?? '', $fonts) ? $brand['font_body'] : 'Inter';
    $fontQuery = collect([$fontHeading, $fontBody])->unique()->map(fn ($f) => $fonts[$f])->implode('|');
    $favicon = \App\Models\MediaAsset::find($settings->get('general.favicon_id'));
    $ga4 = preg_match('/^G-[A-Z0-9]{4,12}$/', (string) $settings->get('analytics.ga4_id')) ? $settings->get('analytics.ga4_id') : null;
    $gtm = preg_match('/^GTM-[A-Z0-9]{4,10}$/', (string) $settings->get('analytics.gtm_id')) ? $settings->get('analytics.gtm_id') : null;
    $canvas = $canvas ?? false;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-public.seo :seo="$seo" />
    @if ($favicon)
        <link rel="icon" href="{{ $favicon->url() }}">
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family={{ $fontQuery }}&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-primary: {{ $hex('primary', '#102C57') }};
            --brand-secondary: {{ $hex('secondary', '#1769AA') }};
            --brand-accent: {{ $hex('accent', '#00B8D9') }};
            --brand-background: {{ $hex('background', '#FFFFFF') }};
            --brand-surface: {{ $hex('surface', '#F5F7FA') }};
            --brand-text: {{ $hex('text', '#172B4D') }};
            --brand-font-heading: '{{ $fontHeading }}';
            --brand-font-body: '{{ $fontBody }}';
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if ($canvas)
        @vite('resources/js/canvas.js')
    @endif
    @if (! $canvas && ! ($previewLabel ?? null))
        @if ($gtm)
            <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',@json($gtm));</script>
        @endif
        @if ($ga4)
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4 }}"></script>
            <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',@json($ga4));</script>
        @endif
        {{-- Custom script: hanya dapat diisi pengguna dengan permission settings.custom_scripts (risiko diterima & diaudit). --}}
        {!! $settings->get('scripts.head') !!}
    @endif
</head>
<body class="flex min-h-screen flex-col">
    @if ($previewLabel ?? null)
        @unless ($canvas)
            <div class="sticky top-0 z-[60] bg-amber-400 px-4 py-2 text-center text-sm font-semibold text-amber-950">
                {{ $previewLabel }} — tidak terlihat oleh pengunjung sampai dipublikasikan.
            </div>
        @endunless
    @endif
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[70] focus:rounded focus:bg-white focus:px-4 focus:py-2">Lewati ke konten</a>

    <x-public.announcement />
    <x-public.header />

    <main id="main" class="flex-1">
        @if (! empty($seo->breadcrumbs) && ($showBreadcrumb ?? true))
            <x-public.breadcrumb :items="$seo->breadcrumbs" />
        @endif
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <x-public.footer />
    <x-public.whatsapp-float />

    @unless ($canvas || ($previewLabel ?? null))
        {!! $settings->get('scripts.body_end') !!}
    @endunless
</body>
</html>
