@if ($paginator->hasPages())
    @php
        $elements = $paginator->toArray()['links'] ?? [];
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $window = [1];
        if ($current - 1 > 1) { $window[] = $current - 1; }
        if ($current !== 1 && $current !== $last) { $window[] = $current; }
        if ($current + 1 < $last) { $window[] = $current + 1; }
        if ($last > 1) { $window[] = $last; }
        $window = array_values(array_unique(array_filter($window, fn ($p) => $p >= 1 && $p <= $last)));
        sort($window);
    @endphp

    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="pgx-pagination">
        <div></div>
        <div>
            <p class="pgx-summary">
                Menampilkan {{ number_format($paginator->firstItem() ?? 0, 0, ',', '.') }}
                sampai {{ number_format($paginator->lastItem() ?? 0, 0, ',', '.') }}
                dari {{ number_format($paginator->total(), 0, ',', '.') }} data
            </p>

            <div class="pgx-pages">
                @if ($paginator->onFirstPage())
                    <span class="pgx-page-btn pgx-page-btn-disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">&#8249;</span>
                @else
                    <a class="pgx-page-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&#8249;</a>
                @endif

                @php $prevShown = null; @endphp
                @foreach ($window as $page)
                    @if (! is_null($prevShown) && ($page - $prevShown) > 1)
                        <span class="pgx-page-dots">&hellip;</span>
                    @endif

                    @if ($page === $current)
                        <span class="pgx-page-btn pgx-page-btn-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pgx-page-btn" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                    @endif
                    @php $prevShown = $page; @endphp
                @endforeach

                @if ($paginator->hasMorePages())
                    <a class="pgx-page-btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&#8250;</a>
                @else
                    <span class="pgx-page-btn pgx-page-btn-disabled" aria-disabled="true" aria-label="@lang('pagination.next')">&#8250;</span>
                @endif
            </div>
        </div>
    </nav>
@endif
