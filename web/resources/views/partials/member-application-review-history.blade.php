@if($application->reviewEvents->isNotEmpty())
    <section class="application-details-section" aria-label="Association review history">
        <h3>Review history</h3>
        @foreach($application->reviewEvents as $event)
            <article class="member-review-history-item">
                <strong>{{ match ($event->action) { 'endorse' => 'Endorsed to Field Officer', 'decline' => 'Endorsement declined', 'return' => 'Returned to association', default => 'Review' } }}</strong>
                <small>{{ $event->actor_name }} · {{ $event->created_at?->timezone('Asia/Manila')->format('M j, Y g:i A') }} PHT</small>
                <p>{{ $event->note }}</p>
            </article>
        @endforeach
    </section>
@endif
