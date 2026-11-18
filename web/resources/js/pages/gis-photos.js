import { photoInputError } from '../components/photo-input.js';

export function createGisPhotos(root, { base, manager, csrf, kind = 'gis', limit = 5 }) {
    const find = selector => root.querySelector(selector);
    const status = find('[data-photo-status]');
    const form = find('[data-photo-upload]');
    const input = form?.querySelector('input[type=file]');
    const preview = find('[data-photo-preview]');
    const viewer = find('[data-photo-viewer]');
    const display = find('[data-photo-display]');
    const confirmation = find('[data-photo-confirm]');
    const grid = find('[data-photo-grid]');
    let current, opener, removalId, previewUrl, requestId, busy = false, canManage = false, slots = 0, photos = [], photoIndex = 0;
    let photoRequest = 0, galleryPage = 0;
    // The public viewer receives its new presentation only on desktop.
    const desktopPublic = () => root.closest('[data-gis-public="true"]') && window.matchMedia('(min-width: 768px)').matches;
    // Use the same photo loading experience for the desktop administrator gallery.
    const enhancedViewer = () => (desktopPublic() || root.closest('[data-gis-administrator="true"]')) && window.matchMedia('(min-width: 768px)').matches;
    const message = text => { status.textContent = text; };
    const resetFile = (clearInput = true) => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null; requestId = null;
        if (input) { if (clearInput) input.value = ''; input.setCustomValidity(''); }
        if (preview) { preview.hidden = true; preview.removeAttribute('src'); }
    };
    const controls = () => {
        root.querySelectorAll('button, input').forEach(control => { control.disabled = busy; });
        if (find('[data-gallery-previous]')) {
            find('[data-gallery-previous]').disabled = busy || galleryPage === 0;
            find('[data-gallery-next]').disabled = busy || (galleryPage + 1) * 4 >= photos.length;
        }
        if (form) {
            form.hidden = !canManage;
            input.disabled = busy || !canManage || slots >= limit;
            const submit = form.querySelector('[type=submit]');
            if (submit) submit.disabled = input.disabled || !input.files[0];
        }
        root.setAttribute('aria-busy', String(busy));
    };
    const request = async (url, method = 'GET', body, signal) => {
        const target = new URL(url, window.location.origin);
        if (target.origin !== window.location.origin) throw new Error('Photo endpoint unavailable.');
        const response = await fetch(target, { method, body, signal, cache: 'no-store', redirect: 'error', credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } });
        const result = await response.json().catch(() => null);
        if (!response.ok || !result || typeof result !== 'object') {
            const errors = Object.values(result?.errors || {}).flat().filter(value => typeof value === 'string');
            throw new Error(errors[0] || result?.message || 'Photos unavailable. Refresh the gallery before retrying.');
        }
        return result;
    };
    const button = (label, action) => {
        const node = document.createElement('button'); node.type = 'button'; node.className = 'button';
        node.textContent = label; node.setAttribute('aria-label', label); node.addEventListener('click', action); return node;
    };
    const formBody = values => {
        const body = new FormData();
        Object.entries(values).forEach(([key, value]) => {
            if (Array.isArray(value)) value.forEach(item => body.append(key + '[]', item));
            else body.append(key, value);
        });
        return body;
    };
    // Show a loading message while the selected full-size photo arrives.
    const showPhoto = index => {
        if (index < 0 || index >= photos.length) return;
        photoIndex = index;
        const photo = photos[index];
        find('[data-photo-prev]').disabled = index === 0;
        find('[data-photo-next]').disabled = index === photos.length - 1;
        find('[data-photo-viewer-count]').textContent = `${index + 1} / ${photos.length}`;
        display.alt = `${kind === 'activity' ? 'Activity' : 'Location'} photo ${index + 1} of ${photos.length}`;
        const imageStatus = find('[data-photo-image-status]');
        const activeRequest = ++photoRequest;
        if (!enhancedViewer()) {
            display.hidden = false;
            imageStatus.classList.remove('is-loading');
            imageStatus.textContent = 'Loading photo...';
            display.onload = () => { imageStatus.textContent = ''; };
            display.onerror = () => { display.removeAttribute('src'); imageStatus.textContent = 'Photo unavailable. Close and refresh the gallery.'; };
            display.src = photo.display_url;
            return;
        }
        display.hidden = true;
        display.removeAttribute('src');
        imageStatus.classList.add('is-loading');
        imageStatus.textContent = 'Loading photo…';
        display.onload = () => {
            if (activeRequest !== photoRequest) return;
            display.hidden = false;
            imageStatus.classList.remove('is-loading');
            imageStatus.textContent = '';
        };
        display.onerror = () => {
            if (activeRequest !== photoRequest) return;
            display.removeAttribute('src');
            imageStatus.classList.remove('is-loading');
            imageStatus.textContent = 'Photo unavailable. Please try again later.';
        };
        display.src = photo.display_url;
    };
    const openPhoto = (photo, source) => {
        const index = photos.findIndex(item => item.id === photo.id);
        if (index < 0) return;
        opener = source; showPhoto(index); viewer.showModal(); find('[data-photo-close]').focus();
    };
    const remove = (id, source) => { removalId = id; opener = source; confirmation.showModal(); find('[data-photo-cancel]').focus(); };
    const render = gallery => {
        if (!Array.isArray(gallery.photos)) throw new Error('Gallery response unavailable.');
        photos = gallery.photos; slots = gallery.used_slots || 0; canManage = manager && gallery.can_manage === true;
        root.dataset.photoState = photos.length ? 'ready' : 'empty';
        find('[data-photo-count]').textContent = manager ? `${slots} / ${limit} slots` : `${photos.length} ${kind === 'gis' ? 'approved ' : ''}${photos.length === 1 ? 'photo' : 'photos'}`;
        const existing = new Map(Array.from(grid.children, node => [node.dataset.photoId, node]));
        const pageCount = Math.max(1, Math.ceil(photos.length / 4));
        galleryPage = Math.min(galleryPage, pageCount - 1);
        const visiblePhotos = kind === 'activity' ? photos.slice(galleryPage * 4, galleryPage * 4 + 4) : photos;
        const pageControls = find('[data-gallery-pages]');
        if (pageControls) {
            pageControls.hidden = pageCount <= 1;
            find('[data-gallery-page]').textContent = `${galleryPage + 1} / ${pageCount}`;
            find('[data-gallery-previous]').disabled = galleryPage === 0;
            find('[data-gallery-next]').disabled = galleryPage >= pageCount - 1;
        }
        grid.replaceChildren(...visiblePhotos.map(photo => {
            const index = photos.indexOf(photo);
            const previous = existing.get(photo.id);
            const figure = document.createElement('figure'); figure.dataset.photoId = photo.id;
            const open = button('View photo ' + (index + 1), () => openPhoto(photo, open));
            open.classList.add('gis-photo-open');
            const caption = document.createElement('span'); caption.textContent = 'View photo ' + (index + 1);
            open.replaceChildren(caption);
            const image = previous?.querySelector('img') || document.createElement('img');
            image.alt = (kind === 'activity' ? 'Activity' : 'Location') + ' photo ' + (index + 1); image.loading = 'lazy'; image.decoding = 'async';
            image.width = photo.thumbnail_width; image.height = photo.thumbnail_height;
            // Show a loading state in the public desktop gallery and retry with the display image if needed.
            if (enhancedViewer()) {
                const loading = document.createElement('span');
                loading.className = 'gis-thumbnail-loading'; loading.textContent = 'Loading photo…';
                loading.setAttribute('role', 'status');
                open.classList.add('is-loading'); open.append(loading);
                image.onload = () => { open.classList.remove('is-loading'); loading.remove(); };
                let fallbackTried = false;
                image.onerror = () => {
                    if (!fallbackTried && photo.display_url && photo.display_url !== photo.thumbnail_url) {
                        fallbackTried = true; image.src = photo.display_url; return;
                    }
                    open.classList.remove('is-loading'); loading.remove(); image.remove();
                    open.textContent = 'Photo unavailable. Refresh to retry';
                };
                if (image.complete && image.naturalWidth > 0) { open.classList.remove('is-loading'); loading.remove(); }
            } else {
                image.onerror = () => { image.remove(); open.textContent = 'Photo unavailable. Refresh to retry'; };
            }
            if (!image.getAttribute('src')) image.src = photo.thumbnail_url;
            open.prepend(image); figure.append(open);
            if (manager && kind === 'gis') {
                const label = document.createElement('figcaption'); label.textContent = photo.approved ? 'Approved for eligible public display' : 'Private'; figure.append(label);
            }
            if (canManage) {
                if (kind === 'gis') figure.append(button(photo.approved ? 'Withdraw approval' : 'Approve for public display', () => mutate(
                    context => request(context.endpoint + '/' + photo.id + '/approval', 'POST', formBody({ _method: 'PATCH', approved: photo.approved ? '0' : '1' })),
                    photo.approved ? 'Approval withdrawn.' : 'Photo approved. Public delivery also requires an eligible published location.')));
                const removeButton = button('Remove photo ' + (index + 1), event => remove(photo.id, event.currentTarget));
                if (kind === 'activity') { removeButton.textContent = '×'; removeButton.classList.add('activity-photo-remove'); }
                figure.append(removeButton);
                if (index > 0) figure.append(button('Move photo ' + (index + 1) + ' earlier', () => {
                    const ids = photos.map(item => item.id); [ids[index - 1], ids[index]] = [ids[index], ids[index - 1]];
                    void mutate(context => request(context.endpoint + '/order', 'POST', formBody({ _method: 'PATCH', ids })), 'Photo order saved.');
                }));
            }
            return figure;
        }));
        const pending = find('[data-photo-pending]'); pending.replaceChildren();
        (gallery.pending || []).forEach((photo, index) => {
            const item = document.createElement('li');
            if (kind === 'gis') {
                item.textContent = photo.status === 'deleting' ? 'Removal awaiting cleanup. ' : 'Upload pending verification. ';
                if (photo.retry_after > 0) item.append(`Check again after ${Math.ceil(photo.retry_after / 60)} minute(s). `);
            } else if (canManage) {
                const label = document.createElement('span');
                // Identify each recovery action, including removals awaiting cleanup.
                label.textContent = photo.status === 'deleting'
                    ? `Photo removal ${index + 1} awaiting cleanup`
                    : `Unfinished photo ${index + 1}`;
                item.append(label);
            }
            const recoveryLabel = kind === 'activity'
                ? (photo.status === 'deleting' ? 'Retry cleanup' : 'Check photo')
                : 'Check saved state';
            if (canManage && photo.retry_after === 0) item.append(button(recoveryLabel, () => mutate(
                context => request(context.endpoint + '/' + photo.id + '/reconcile', 'POST'), 'Saved state checked.')));
            if (canManage && photo.status !== 'deleting') {
                const removeButton = button(kind === 'activity' ? `Remove unfinished photo ${index + 1}` : 'Remove pending photo', event => remove(photo.id, event.currentTarget));
                if (kind === 'activity') { removeButton.textContent = '×'; removeButton.classList.add('activity-pending-remove'); }
                item.append(removeButton);
            }
            if (item.hasChildNodes()) pending.append(item);
        });
        message(photos.length ? '' : (manager ? 'No ready photos. Pending uploads appear below.' : (kind === 'gis' ? 'No approved photos are available for this location.' : 'No photos have been added yet.')));
        controls();
    };
    const load = async context => {
        const gallery = await request(context.endpoint, 'GET', undefined, context.controller.signal);
        if (current === context) render(gallery);
    };
    const refresh = async () => {
        if (!current || busy) return;
        const context = current; busy = true; root.dataset.photoState = 'loading'; controls(); message('Loading photos...');
        try { await load(context); } catch (error) {
            if (current === context) { root.dataset.photoState = 'unavailable'; grid.replaceChildren(); display.removeAttribute('src'); viewer.close(); canManage = false; message(error.message); }
        } finally { if (current === context) { busy = false; controls(); } }
    };
    const mutate = async (action, success, isUpload = false) => {
        if (!current || busy || !canManage) return;
        const context = current; busy = true; controls();
        const uploadButton = form?.querySelector('[type=submit]');
        if (kind === 'activity' && isUpload) {
            status.classList.add('is-saving');
            if (uploadButton) uploadButton.textContent = 'Saving photo…';
            message('Saving your photo… Please wait.');
        } else message('Saving photo changes...');
        try {
            await action(context);
            if (current !== context) return;
            await load(context);
            if (current === context) message(success);
        } catch (error) {
            if (current !== context) return;
            try { await load(context); } catch { grid.replaceChildren(); canManage = false; }
            if (current === context) message(error.message);
        } finally {
            if (current === context) {
                busy = false; status.classList.remove('is-saving');
                if (uploadButton && kind === 'activity' && isUpload) uploadButton.textContent = 'Upload photo';
                controls();
            }
        }
    };
    const uploadSelected = () => {
        const error = photoInputError(input.files[0]);
        if (error) { message(error); return; }
        const body = formBody({ photo: input.files[0], request_id: requestId ||= crypto.randomUUID() });
        void mutate(async context => { await request(context.endpoint, 'POST', body); if (current === context) resetFile(); }, 'Photo uploaded privately.', true);
    };
    input?.addEventListener('change', () => {
        const file = input.files[0]; resetFile(false);
        const error = photoInputError(file); input.setCustomValidity(error); message(error);
        if (!error) { requestId = crypto.randomUUID(); previewUrl = URL.createObjectURL(file); preview.src = previewUrl; preview.hidden = false; }
        controls();
        if (!error && kind === 'activity') uploadSelected();
    });
    form?.addEventListener('submit', event => {
        event.preventDefault();
        if (kind !== 'activity') uploadSelected();
    });
    find('[data-photo-refresh]').addEventListener('click', refresh);
    find('[data-gallery-previous]')?.addEventListener('click', () => { if (galleryPage > 0) { galleryPage--; void refresh(); } });
    find('[data-gallery-next]')?.addEventListener('click', () => { if ((galleryPage + 1) * 4 < photos.length) { galleryPage++; void refresh(); } });
    // Keep the existing photo controls usable with a mouse or keyboard.
    find('[data-photo-close]').addEventListener('click', () => viewer.close());
    find('[data-photo-prev]').addEventListener('click', () => showPhoto(photoIndex - 1));
    find('[data-photo-next]').addEventListener('click', () => showPhoto(photoIndex + 1));
    viewer.addEventListener('keydown', event => {
        if (event.key === 'ArrowLeft') { event.preventDefault(); showPhoto(photoIndex - 1); }
        if (event.key === 'ArrowRight') { event.preventDefault(); showPhoto(photoIndex + 1); }
    });
    viewer.addEventListener('close', () => { photoRequest++; display.removeAttribute('src'); display.hidden = false; find('[data-photo-image-status]').classList.remove('is-loading'); opener?.isConnected && opener.focus(); });
    if (confirmation) {
        find('[data-photo-cancel]').addEventListener('click', () => confirmation.close());
        find('[data-photo-remove]').addEventListener('click', () => { confirmation.close(); if (removalId) void mutate(
            context => request(context.endpoint + '/' + removalId, 'DELETE'), 'Photo removed. Pending cleanup is shown below.'); });
        confirmation.addEventListener('close', () => { opener?.isConnected && opener.focus(); });
    }
    const clear = () => {
        current?.controller.abort(); current = null; busy = false; canManage = false; photos = []; slots = 0; galleryPage = 0;
        resetFile(); status.classList.remove('is-saving'); viewer.close(); confirmation?.close(); display.removeAttribute('src');
        grid.replaceChildren(); find('[data-photo-pending]').replaceChildren(); root.hidden = true;
    };
    window.addEventListener('pagehide', clear, { once: true });
    return { clear, select(id, version) {
        if (current?.id === id && current?.version === version) return;
        clear(); current = { id, version, endpoint: `${base}/${encodeURIComponent(id)}/photos`, controller: new AbortController() };
        root.hidden = false; void refresh();
    } };
}
