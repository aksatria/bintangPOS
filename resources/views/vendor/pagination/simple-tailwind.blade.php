@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="pgx-pagination">
        <div></div>
        <div>
            <p class="pgx-summary">
                Halaman {{ number_format($paginator->currentPage(), 0, ',', '.') }}
            </p>
            <div class="pgx-pages">
                @if ($paginator->onFirstPage())
                    <span class="pgx-page-btn pgx-page-btn-disabled" aria-disabled="true">&#8249;</span>
                @else
                    <a class="pgx-page-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">&#8249;</a>
                @endif

                @if ($paginator->hasMorePages())
                    <a class="pgx-page-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">&#8250;</a>
                @else
                    <span class="pgx-page-btn pgx-page-btn-disabled" aria-disabled="true">&#8250;</span>
                @endif
            </div>
        </div>
    </nav>
@endif
