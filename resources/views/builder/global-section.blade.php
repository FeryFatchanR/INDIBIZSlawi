@php $globalHtml = $renderer->renderGlobal(isset($content['page_id']) ? (int) $content['page_id'] : null); @endphp
@if ($globalHtml !== '')
    {!! $globalHtml !!}
@elseif ($editor)
    <div class="mx-auto max-w-6xl rounded border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Global section belum dipilih atau belum dipublikasikan.</div>
@endif
