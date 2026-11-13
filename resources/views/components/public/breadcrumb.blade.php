@props(['items' => []])
<nav aria-label="Breadcrumb" class="border-b border-slate-100 bg-white">
    <ol class="mx-auto flex max-w-7xl flex-wrap items-center gap-1 px-4 py-3 text-xs text-slate-500 sm:px-6 lg:px-8">
        @foreach ($items as $i => [$label, $url])
            <li class="flex items-center gap-1">
                @if ($i > 0)<span aria-hidden="true">/</span>@endif
                @if ($url && ! $loop->last)
                    <a href="{{ $url }}" class="hover:text-secondary">{{ $label }}</a>
                @else
                    <span @if ($loop->last) aria-current="page" class="font-medium text-ink" @endif>{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
