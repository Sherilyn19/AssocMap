<footer class="table-footer member-register-pagination">
    <span>Showing {{ number_format($paginator->firstItem() ?? 0) }}–{{ number_format($paginator->lastItem() ?? 0) }} of {{ number_format($paginator->total()) }} {{ $recordLabel }}@if($indexRoute === 'admin.members.index') <span aria-hidden="true">·</span> Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}@endif</span>
    <nav aria-label="{{ ucfirst($recordLabel) }} pages">
        @if($indexRoute === 'admin.members.index')
            @if($paginator->previousPageUrl())<a href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>@else<span class="member-page-disabled" aria-disabled="true">Previous</span>@endif
            @if($paginator->nextPageUrl())<a href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>@else<span class="member-page-disabled" aria-disabled="true">Next</span>@endif
        @else
            @if($paginator->previousPageUrl())<a class="button" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>@endif
            <span>Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
            @if($paginator->nextPageUrl())<a class="button" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>@endif
        @endif
    </nav>
</footer>
