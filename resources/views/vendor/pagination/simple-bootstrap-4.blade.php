@if ($paginator->hasPages())
    <div class="pagination pull-left">
    @if (!$paginator->onFirstPage())
        <div class="page-item">
            <a class="btn btn-light float-left"
               href="{{ $paginator->previousPageUrl() }}" title="이전" rel="이전">이전</a>
        </div>
    @endif
    </div>

    <div class="pagination pull-right">
    @if ($paginator->hasMorePages())
        <div class="page-item">
            <a class="btn btn-light float-right"
               href="{{ $paginator->nextPageUrl() }}" title="다음" rel="다음">다음</a>
        </div>
    @endif
    </div>
@endif
