<div class="monitoring-pagination">
    <p role="status">Showing {{ number_format($paginator->firstItem() ?? 0) }}–{{ number_format($paginator->lastItem() ?? 0) }} of {{ number_format($paginator->total()) }} {{ $label }} · Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</p>
    <nav aria-label="{{ ucfirst($label) }} pages">@if($paginator->previousPageUrl())<a href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>@else<span aria-disabled="true">Previous</span>@endif @if($paginator->nextPageUrl())<a href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>@else<span aria-disabled="true">Next</span>@endif</nav>
</div>
