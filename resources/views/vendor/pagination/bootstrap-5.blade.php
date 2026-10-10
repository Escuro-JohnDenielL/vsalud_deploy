{{-- Shared pagination view (Bootstrap 5 markup), green palette.
     Overrides the framework default: resources/views/vendor/pagination/bootstrap-5.blade.php --}}
@if ($paginator->hasPages())
    <div class="vs-pagination">
        <ul class="pagination" role="list">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="Previous">
                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous">&lsaquo;</a>
                </li>
            @endif

            {{-- Page links (Laravel supplies first, last and a window around the current page) --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next">&rsaquo;</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="Next">
                    <span class="page-link" aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>

        <p class="vs-pagination__summary" role="status">
            Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} results
        </p>
    </div>
@endif
