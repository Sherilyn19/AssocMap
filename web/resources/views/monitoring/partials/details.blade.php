<dl class="monitoring-detail-grid">
    @foreach($details as $label => $value)
        <div><dt>{{ $label }}</dt><dd>@if(in_array($label, $badgeLabels ?? [], true) && filled($value))<x-status-badge :value="$value" />@else{{ filled($value) ? $value : 'Not recorded' }}@endif</dd></div>
    @endforeach
</dl>
