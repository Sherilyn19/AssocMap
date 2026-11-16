<nav class="member-summary summary-cards" aria-label="{{ $applicationRegister ? 'Application' : 'Member' }} status groups">
    @foreach($cards as $key => [$label, $value, $icon])
        <a href="{{ route($indexRoute, array_replace($filters, ['status' => $value])) }}" @if($filters['status'] === $value) aria-current="page" @endif>
            @if($indexRoute === 'admin.members.index')<x-stat-card :label="$label" :value="number_format($summary[$key])" :icon="$icon" />@else<x-stat-card :label="$label" :value="number_format($summary[$key])" :icon="$icon" note="View matching records" :animate="false" />@endif
        </a>
    @endforeach
</nav>
@if($indexRoute === 'admin.members.index')<div class="member-info-row"><span>About these totals</span><x-area-info label="About member totals">Current, active, and inactive totals exclude archived members. Counts cover authorized records before filters.</x-area-info></div>@elseif($indexRoute !== 'association.member-applications.index')<p class="member-summary-help">{{ $summaryNote }} Counts cover your authorized records before search and filters.</p>@endif
