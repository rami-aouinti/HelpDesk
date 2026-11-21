@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="helpdesk-pagination">
        <div class="pagination-summary">
            <span>Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
        </div>
        <ul class="pagination-list">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="pagination-control is-disabled" aria-disabled="true">Previous</span>
                @else
                    <a class="pagination-control" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
                @endif
            </li>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="pagination-ellipsis">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="pagination-page is-active" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="pagination-page" href="{{ $url }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
            <li>
                @if ($paginator->hasMorePages())
                    <a class="pagination-control" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
                @else
                    <span class="pagination-control is-disabled" aria-disabled="true">Next</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
