<div data-feedback-root>
    @if($payload['notification'])
        <section class="notice notice-{{ $payload['notification']['type'] === 'success' ? 'success' : 'error' }}" data-feedback-fallback role="{{ $payload['notification']['type'] === 'success' ? 'status' : 'alert' }}">
            <strong>{{ $payload['notification']['title'] }}</strong><p>{{ $payload['notification']['message'] }}</p>
        </section>
    @endif
    <script type="application/json" data-feedback-payload>@json($payload)</script>
    <dialog id="global-feedback-modal" class="feedback-modal" data-global-feedback role="alertdialog" aria-modal="true" aria-labelledby="global-feedback-title" aria-describedby="global-feedback-message">
        <div class="feedback-content">
            <span class="feedback-icon" aria-hidden="true"><span data-feedback-success><x-icon name="check" /></span><span data-feedback-error hidden><svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.852l.041-.02M12 8.25h.008v.008H12V8.25ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg></span></span>
            <h2 id="global-feedback-title" data-feedback-title></h2>
            <p id="global-feedback-message" data-feedback-message></p>
            <div class="feedback-actions"><button type="button" class="button button-primary" data-feedback-acknowledge autofocus>OK</button></div>
        </div>
    </dialog>
</div>
