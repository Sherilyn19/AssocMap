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
        let page = 1, lastPage = 1, opener = null;

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
            grid.replaceChildren(); status.textContent = 'Loading GIS photos…';
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
                            img.onerror = () => { button.remove(); status.textContent = grid.children.length ? 'Some photos could not be displayed. Try again later or visit the GIS map.' : 'Photos could not be displayed. Try again later or visit the GIS map.'; };
                            button.append(img); button.addEventListener('click', () => {
                                opener = button; image.alt = `Photo of ${title}`; image.src = display;
                                root.querySelector('[data-gallery-caption]').textContent = title;
                                viewer.showModal(); root.querySelector('[data-gallery-close]').focus();
                            });
                            grid.append(button); count++;
                        } catch { failed++; }
                    });
                });
                status.textContent = failed ? (count ? 'Some location photos could not be loaded. Try again later.' : 'Photos could not be loaded. Try again later or visit the GIS map.') : (count ? 'Approved photos from your GIS locations.' : 'No approved photos are available for the locations on this page.');
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
        viewer.addEventListener('close', () => { image.removeAttribute('src'); opener?.focus(); });
        image.addEventListener('error', () => { root.querySelector('[data-gallery-caption]').textContent = 'Photo unavailable.'; });
        void load();
    });
}
