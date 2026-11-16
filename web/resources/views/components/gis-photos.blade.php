<section class="gis-photos" data-gis-photos hidden aria-label="Location photos">
    <div class="gis-photos-heading"><div><div class="gis-photo-title"><h3>Photo gallery</h3>@if($gisAdministrator ?? false)<x-area-info label="About photo visibility">Photos are private until approved for an eligible published location. Evidence for finalized projects is read-only.</x-area-info>@endif</div><span data-photo-count @if($gisPublic ?? false) hidden @endif></span></div><button type="button" class="button" data-photo-refresh aria-label="Refresh location photos">Refresh</button></div>
    <p class="gis-photo-status" data-photo-status role="status" aria-live="polite"></p>
    @if($gisManager)
        @unless($gisAdministrator ?? false)<p class="gis-photo-policy">Private by default. Approve each photo explicitly for a published, eligible location. Finalized project evidence is read-only.</p>@endunless
        <form data-photo-upload>
            <label for="gis-photo-file">JPEG or PNG, at most 5 MiB</label>
            <input id="gis-photo-file" type="file" accept="image/jpeg,image/png,.jpg,.jpeg,.png" required>
            <img data-photo-preview hidden alt="Selected photo preview" class="gis-photo-preview">
            <button type="submit" class="button button-primary">Upload photo</button>
        </form>
    @elseif(!($gisPublic ?? false) && !request()->routeIs('association.gis.*'))
        <p class="gis-photo-policy">Approved photos of published locations.</p>
    @endif
    <div class="gis-photo-grid" data-photo-grid></div>
    <ul data-photo-pending></ul>
    <dialog class="modal gis-photo-viewer" data-photo-viewer aria-label="Location photo">
        <button type="button" class="gis-photo-viewer-close" data-photo-close aria-label="Close photo"><x-icon name="close" /></button>
        <p class="gis-photo-viewer-status" data-photo-image-status role="status" aria-live="polite"></p>
        <img data-photo-display alt="Location photo">
        <button type="button" class="gis-photo-viewer-nav gis-photo-viewer-prev" data-photo-prev aria-label="Previous photo">&#8249;</button>
        <button type="button" class="gis-photo-viewer-nav gis-photo-viewer-next" data-photo-next aria-label="Next photo">&#8250;</button>
        <p class="gis-photo-viewer-count" data-photo-viewer-count aria-live="polite"></p>
    </dialog>
    @if($gisManager)
        <dialog class="modal" data-photo-confirm aria-labelledby="gis-photo-remove-title">
            <h3 id="gis-photo-remove-title">Remove location photo?</h3>
            <p>The photo will stop being accessible immediately. Storage cleanup may remain pending.</p>
            <div class="modal-actions"><button type="button" class="button" data-photo-cancel>Cancel</button><button type="button" class="button button-danger" data-photo-remove>Remove photo</button></div>
        </dialog>
    @endif
</section>
