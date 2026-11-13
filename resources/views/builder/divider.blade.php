@php
    use App\Builder\Style\StyleTokens;
    $line = ['solid' => 'border-solid', 'dashed' => 'border-dashed', 'dotted' => 'border-dotted'][$design['line_style'] ?? 'solid'] ?? 'border-solid';
@endphp
<div class="{{ StyleTokens::container($design['container'] ?? 'normal') }}"><hr class="border-t {{ $line }} {{ $isDark ? 'border-white/20' : 'border-slate-200' }}"></div>
