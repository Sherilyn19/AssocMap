<section class="gis-photos" data-gis-photos hidden aria-label="{{ ($activityGallery ?? false) ? 'Program activity photos' : 'Location photos' }}">
    <div class="gis-photos-heading"><div><div class="gis-photo-title"><h3>Photo gallery</h3>@if($gisAdministrator ?? false)<x-area-info label="About photo visibility">Photos are private until approved for an eligible published location. Evidence for finalized projects is read-only.</x-area-info>@endif</div><span data-photo-count @if($gisPublic ?? false) hidden @endif></span></div><button type="button" class="button" data-photo-refresh aria-label="{{ ($activityGallery ?? false) ? 'Refresh activity photos' : 'Refresh location photos' }}">Refresh</button></div>
    <p class="gis-photo-status" data-photo-status role="status" aria-live="polite"></p>
    @if($gisManager)
        @if($activityGallery ?? false)<p class="gis-photo-policy">Photos are private to authorized users. Up to 20 photos may be added.</p>@else @unless($gisAdministrator ?? false)<p class="gis-photo-policy">Private by default. Approve each photo explicitly for a published, eligible location. Finalized project evidence is read-only.</p>@endunless @endif
        <form data-photo-upload>
            <label for="gis-photo-file">{{ ($activityGallery ?? false) ? 'Choose a photo to upload' : 'JPEG or PNG, at most 5 MiB' }}</label>
            <input id="gis-photo-file" type="file" accept="image/jpeg,image/png,.jpg,.jpeg,.png" required>
            @if($activityGallery ?? false)<small>JPEG or PNG, at most 5 MiB</small>@endif
            <img data-photo-preview hidden alt="Selected photo preview" class="gis-photo-preview">
            @unless($activityGallery ?? false)<button type="submit" class="button button-primary">Upload photo</button>@endunless
        </form>
    @elseif(!($activityGallery ?? false) && !($gisPublic ?? false) && !request()->routeIs('association.gis.*'))
        <p class="gis-photo-policy">Approved photos of published locations.</p>
    @endif
    <div class="gis-photo-grid" data-photo-grid></div>
    @if($activityGallery ?? false)<nav class="activity-gallery-pages" aria-label="Activity gallery pages" data-gallery-pages hidden><button type="button" class="button" data-gallery-previous aria-label="Previous photos">‹</button><span data-gallery-page></span><button type="button" class="button" data-gallery-next aria-label="Next photos">›</button></nav>@endif
    <ul data-photo-pending></ul>
    {{-- Show each location photo in a keyboard-accessible viewer. --}}
    <dialog class="modal gis-photo-viewer" data-photo-viewer aria-label="{{ ($activityGallery ?? false) ? 'Activity photo' : 'Location photo' }}">
        <button type="button" class="gis-photo-viewer-close" data-photo-close aria-label="Close photo"><x-icon name="close" /></button>
        <p class="gis-photo-viewer-status" data-photo-image-status role="status" aria-live="polite"></p>
        <img data-photo-display alt="Location photo">
        <button type="button" class="gis-photo-viewer-nav gis-photo-viewer-prev" data-photo-prev aria-label="Previous photo">@if(($gisPublic ?? false) || ($gisAdministrator ?? false))<x-icon name="chevron-right" class="gis-public-desktop-arrow" /><span class="gis-public-original-arrow">&#8249;</span>@else&#8249;@endif</button>
        <button type="button" class="gis-photo-viewer-nav gis-photo-viewer-next" data-photo-next aria-label="Next photo">@if(($gisPublic ?? false) || ($gisAdministrator ?? false))<x-icon name="chevron-right" class="gis-public-desktop-arrow" /><span class="gis-public-original-arrow">&#8250;</span>@else&#8250;@endif</button>
        <p class="gis-photo-viewer-count" data-photo-viewer-count aria-live="polite"></p>
    </dialog>
    @if($gisManager)
        <dialog class="modal" data-photo-confirm aria-labelledby="gis-photo-remove-title">
            <h3 id="gis-photo-remove-title">Remove {{ ($activityGallery ?? false) ? 'activity' : 'location' }} photo?</h3>
            <p>The photo will stop being accessible immediately. Storage cleanup may remain pending.</p>
            <div class="modal-actions"><button type="button" class="button" data-photo-cancel>Cancel</button><button type="button" class="button button-danger" data-photo-remove>Remove photo</button></div>
        </dialog>
    @endif
</section>
