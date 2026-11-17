export function initDashboardGisGallery() {
    document.querySelectorAll('[data-dashboard-gis-gallery]').forEach(root => {
        const base = root.dataset.gisBase.replace(/\/$/, '');
        const grid = root.querySelector('[data-gallery-grid]');
        const status = root.querySelector('[data-gallery-status]');
        const previous = root.querySelector('[data-gallery-previous]');
        const next = root.querySelector('[data-gallery-next]');
        const pageLabel = root.querySelector('[data-gallery-page]');
        const viewer = root.querySelector('[data-gallery-viewer]');
        const image = root.querySelector('[data-gallery-image]');
        const viewerStatus = root.querySelector('[data-viewer-status]');
        const viewerPrevious = root.querySelector('[data-viewer-previous]');
        const viewerNext = root.querySelector('[data-viewer-next]');
        let page = 1, lastPage = 1, opener = null, photos = [], photoIndex = 0, touchStart = null, photoRequest = 0;

        const showPhoto = index => {
            if (index < 0 || index >= photos.length) return;
            photoIndex = index;
            const photo = photos[index];
            const request = ++photoRequest;
            image.hidden = true;
            image.removeAttribute('src');
            image.alt = `Photo of ${photo.title}`;
            viewerStatus.hidden = false;
            viewerStatus.classList.add('is-loading');
            viewerStatus.textContent = 'Loading photo…';
            image.onload = () => {
                if (request !== photoRequest) return;
                image.hidden = false;
                viewerStatus.hidden = true;
                viewerStatus.classList.remove('is-loading');
                viewerStatus.textContent = '';
            };
            image.onerror = () => {
                if (request !== photoRequest) return;
                viewerStatus.classList.remove('is-loading');
                viewerStatus.textContent = 'Photo unavailable. Please try again later.';
            };
            image.src = photo.display;
            root.querySelector('[data-gallery-caption]').textContent = photo.title;
            root.querySelector('[data-viewer-count]').textContent = `${index + 1} / ${photos.length}`;
            viewerPrevious.disabled = index === 0; viewerNext.disabled = index === photos.length - 1;
        };

        const get = async (path, allowUnavailableLocation = false) => {
            const url = new URL(path, window.location.origin);
            if (url.origin !== window.location.origin) throw new Error('Invalid photo address');
            const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', redirect: 'error', headers: { Accept: 'application/json' } });
            if (response.status === 404 && allowUnavailableLocation) return { photos: [] };
            if (!response.ok) throw new Error('Gallery unavailable');
            return response.json();
        };
        const uuid = value => typeof value === 'string' && /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(value);
        const load = async () => {
            previous.disabled = next.disabled = true;
            grid.replaceChildren(); photos = []; status.textContent = 'Loading GIS photos…';
            try {
                const data = await get(`${base}/data?mode=list&page=${page}`);
                if (!Array.isArray(data.features) || !data.meta) throw new Error('Locations unavailable');
                lastPage = Math.max(1, Number(data.meta.last_page) || 1);
                const results = await Promise.allSettled(data.features.map(async feature => ({
                    id: feature.id,
                    title: feature.properties?.title || 'GIS location',
                    gallery: await get(`/map/${encodeURIComponent(feature.id)}/photos`, true),
                })));
                let count = 0, failed = 0;
                results.forEach(result => {
                    if (result.status !== 'fulfilled') { failed++; return; }
                    const { id, title, gallery } = result.value;
                    if (!Array.isArray(gallery.photos)) { failed++; return; }
                    gallery.photos.forEach(photo => {
                        try {
                            if (!uuid(id) || !uuid(photo.id)) throw new Error('Invalid photo reference');
                            const photoPath = `/map/${id}/photos/${photo.id}`;
                            const thumbnail = `${photoPath}/thumbnail`, display = `${photoPath}/display`;
                            const button = document.createElement('button'); button.type = 'button';
                            button.setAttribute('aria-label', `View photo of ${title}`);
                            const img = document.createElement('img'); img.src = thumbnail; img.alt = `Photo of ${title}`; img.loading = 'lazy';
                            const photoEntry = { title, display };
                            photos.push(photoEntry);
                            img.onerror = () => { const index = photos.indexOf(photoEntry); if (index !== -1) photos.splice(index, 1); button.remove(); status.textContent = grid.children.length ? 'Some photos could not be displayed. Try again later or visit the GIS map.' : 'Photos could not be displayed. Try again later or visit the GIS map.'; };
                            button.append(img); button.addEventListener('click', () => {
                                opener = button; showPhoto(photos.indexOf(photoEntry));
                                viewer.showModal(); root.querySelector('[data-gallery-close]').focus();
                            });
                            grid.append(button); count++;
                        } catch { failed++; }
                    });
                });
                status.textContent = failed ? (count ? 'Some location photos could not be loaded. Try again later.' : 'Photos could not be loaded. Try again later or visit the GIS map.') : (count ? '' : 'No approved photos are available for the locations on this page.');
                pageLabel.textContent = `Locations page ${page} of ${lastPage}`;
                previous.disabled = page <= 1; next.disabled = page >= lastPage;
            } catch {
                status.textContent = 'The gallery could not be loaded. Try again later or visit the GIS map.';
                pageLabel.textContent = '';
            }
        };
        previous.addEventListener('click', () => { if (page > 1) { page--; void load(); } });
        next.addEventListener('click', () => { if (page < lastPage) { page++; void load(); } });
        root.querySelector('[data-gallery-close]').addEventListener('click', () => viewer.close());
        viewerPrevious.addEventListener('click', () => showPhoto(photoIndex - 1));
        viewerNext.addEventListener('click', () => showPhoto(photoIndex + 1));
        viewer.addEventListener('keydown', event => {
            if (event.key === 'ArrowLeft') { event.preventDefault(); showPhoto(photoIndex - 1); }
            if (event.key === 'ArrowRight') { event.preventDefault(); showPhoto(photoIndex + 1); }
        });
        image.addEventListener('touchstart', event => { touchStart = event.changedTouches[0].clientX; }, { passive: true });
        image.addEventListener('touchend', event => { if (touchStart === null) return; const distance = event.changedTouches[0].clientX - touchStart; if (Math.abs(distance) > 50) showPhoto(photoIndex + (distance < 0 ? 1 : -1)); touchStart = null; }, { passive: true });
        viewer.addEventListener('close', () => { photoRequest++; image.removeAttribute('src'); image.hidden = true; viewerStatus.hidden = true; opener?.focus(); });
        void load();
    });
}
