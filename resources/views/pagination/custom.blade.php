@if ($paginator->hasPages())
<nav role="navigation" aria-label="Paginación" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

    <p class="text-[12px] text-[var(--text-500)]">
        Mostrando
        @if ($paginator->firstItem())
            <span class="font-medium text-[var(--text-700)]">{{ $paginator->firstItem() }}</span>
            a
            <span class="font-medium text-[var(--text-700)]">{{ $paginator->lastItem() }}</span>
        @else
            {{ $paginator->count() }}
        @endif
        de
        <span class="font-medium text-[var(--text-700)]">{{ $paginator->total() }}</span>
        resultados
    </p>

    <div class="flex items-center gap-1">
        {{-- Anterior --}}
        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-[var(--radius-control)] text-[var(--text-400)] cursor-not-allowed opacity-40">
                <x-lucide-chevron-left class="w-4 h-4" />
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
               class="inline-flex items-center justify-center w-8 h-8 rounded-[var(--radius-control)] text-[var(--text-500)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-700)]">
                <x-lucide-chevron-left class="w-4 h-4" />
            </a>
        @endif

        {{-- Números de página --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="inline-flex items-center justify-center w-8 h-8 text-[12.5px] text-[var(--text-400)]">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page"
                              class="inline-flex items-center justify-center w-8 h-8 rounded-[var(--radius-control)] bg-[var(--color-primary)] text-white text-[12.5px] font-medium">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}"
                           class="inline-flex items-center justify-center w-8 h-8 rounded-[var(--radius-control)] text-[12.5px] text-[var(--text-500)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-700)]">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Siguiente --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next"
               class="inline-flex items-center justify-center w-8 h-8 rounded-[var(--radius-control)] text-[var(--text-500)] hover:bg-[var(--surface-muted)] hover:text-[var(--text-700)]">
                <x-lucide-chevron-right class="w-4 h-4" />
            </a>
        @else
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-[var(--radius-control)] text-[var(--text-400)] cursor-not-allowed opacity-40">
                <x-lucide-chevron-right class="w-4 h-4" />
            </span>
        @endif
    </div>
</nav>
@endif
