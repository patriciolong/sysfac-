@if ($paginator->hasPages())
    <div class="custom-pagination-wrapper">
        <div class="pagination-info">
            Mostrando
            @if ($paginator->firstItem())
                <strong>{{ $paginator->firstItem() }}</strong>
                a
                <strong>{{ $paginator->lastItem() }}</strong>
            @else
                <strong>{{ $paginator->count() }}</strong>
            @endif
            de
            <strong>{{ $paginator->total() }}</strong>
            resultados
        </div>

        <nav class="pagination-nav" aria-label="Navegación de páginas">
            {{-- Botón Anterior --}}
            @if ($paginator->onFirstPage())
                <span class="pagination-item pagination-disabled" aria-disabled="true" title="Página anterior">
                    <i class="fa-solid fa-chevron-left"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-item" title="Página anterior">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
            @endif

            {{-- Elementos de paginación --}}
            @foreach ($elements as $element)
                {{-- Separador "..." --}}
                @if (is_string($element))
                    <span class="pagination-item pagination-ellipsis" aria-disabled="true">{{ $element }}</span>
                @endif

                {{-- Enlaces de páginas --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-item pagination-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="pagination-item" aria-label="Ir a página {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Botón Siguiente --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-item" title="Página siguiente">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            @else
                <span class="pagination-item pagination-disabled" aria-disabled="true" title="Página siguiente">
                    <i class="fa-solid fa-chevron-right"></i>
                </span>
            @endif
        </nav>
    </div>
@endif
