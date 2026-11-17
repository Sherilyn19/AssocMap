export function initMemberEvidencePhotos() {
    document.querySelectorAll('[data-member-evidence-photo]').forEach(root => {
        const link = root.querySelector('[data-evidence-open]');
        const thumbnail = link.querySelector('img');
        const status = root.querySelector('[data-evidence-status]');
        const viewer = root.querySelector('[data-evidence-viewer]');
        const image = root.querySelector('[data-evidence-image]');
        const viewerStatus = root.querySelector('[data-evidence-viewer-status]');
        const close = root.querySelector('[data-evidence-close]');
        const fallback = root.querySelector('[data-evidence-fallback]');

        // Show a clear message if the small photo cannot be displayed.
        const thumbnailLoaded = () => { status.hidden = true; };
        const thumbnailFailed = () => {
            link.hidden = true;
            // Use initials for member profiles when their photo is unavailable.
            if (fallback) fallback.hidden = false;
            status.hidden = Boolean(fallback);
            status.classList.remove('is-loading');
            status.textContent = 'Photo unavailable. Refresh the page to check your access or try again.';
        };
        thumbnail.addEventListener('load', thumbnailLoaded);
        thumbnail.addEventListener('error', thumbnailFailed);
        // The photo may already have loaded before these listeners were added.
        if (thumbnail.complete && thumbnail.currentSrc) {
            if (thumbnail.naturalWidth) thumbnailLoaded(); else thumbnailFailed();
        } else {
            status.hidden = false;
            status.classList.add('is-loading');
        }

        // Open a larger view; the link still works if the viewer is unavailable.
        link.addEventListener('click', event => {
            if (typeof viewer.showModal !== 'function') return;
            event.preventDefault();
            viewerStatus.hidden = false;
            viewerStatus.textContent = 'Loading photo…';
            viewerStatus.classList.add('is-loading');
            image.hidden = true;
            image.onload = () => { viewerStatus.hidden = true; viewerStatus.classList.remove('is-loading'); image.hidden = false; };
            image.onerror = () => { viewerStatus.classList.remove('is-loading'); viewerStatus.textContent = 'Photo unavailable. Please try again later.'; };
            image.src = link.href;
            viewer.showModal();
            close.focus();
        });
        close.addEventListener('click', () => viewer.close());
        // Clear the larger photo and return focus to the original link.
        viewer.addEventListener('close', () => {
            image.onload = null;
            image.onerror = null;
            image.removeAttribute('src');
            image.hidden = true;
            link.focus();
        });
    });
}
