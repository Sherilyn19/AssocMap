// A device reading is only a draft until the user explicitly chooses it.
export function initCurrentLocation({ button, panel, status, use, cancel, geolocation, preview, clearPreview, apply, isBoundary }) {
    let watchId = null, timer = null, best = null, sequence = 0, running = false;

    function stop() {
        running = false;
        sequence++;
        if (watchId !== null) geolocation?.clearWatch(watchId);
        watchId = null;
        clearTimeout(timer); timer = null;
        button.disabled = false; button.textContent = 'Use current location';
    }

    function dismiss() {
        stop(); best = null; panel.hidden = true; use.hidden = true;
        clearPreview();
    }

    button.addEventListener('click', () => {
        dismiss(); panel.hidden = false;
        if (!geolocation) {
            status.textContent = 'Location is unavailable in this browser. Use search, the map, or coordinates instead.';
            return;
        }
        running = true;
        const current = ++sequence;
        button.disabled = true; button.textContent = 'Finding location…';
        status.textContent = 'Requesting permission and looking for a fresh high-accuracy reading…';
        timer = setTimeout(() => {
            if (current !== sequence) return;
            stop();
            status.textContent = best
                ? `Best reported accuracy: ±${Math.ceil(best.accuracy)} m. Check the location on the map before using it.`
                : 'No location received. Check permission and device location settings, then try again.';
        }, 20000);
        try {
            watchId = geolocation.watchPosition(position => {
                if (!running || current !== sequence) return;
                const { latitude, longitude, accuracy } = position.coords || {};
                if (![latitude, longitude, accuracy].every(Number.isFinite) || Math.abs(latitude) > 90 || Math.abs(longitude) > 180 || accuracy < 0
                    || !Number.isFinite(position.timestamp) || Date.now() - position.timestamp > 30000 || position.timestamp > Date.now() + 5000) return;
                if (!best || accuracy < best.accuracy) {
                    const first = !best;
                    best = { latitude, longitude, accuracy };
                    preview(best, first);
                    use.hidden = false;
                }
                status.textContent = `Best reported accuracy: ±${Math.ceil(best.accuracy)} m. Looking for a better reading; check the location before using it.`;
            }, error => {
                if (current !== sequence) return;
                stop();
                status.textContent = best ? `Location updates stopped. Best reported accuracy: ±${Math.ceil(best.accuracy)} m. Check it before using.`
                    : error.code === 1 ? 'Location permission was denied. Allow location access in your browser settings to try again.'
                    : 'Location is unavailable or timed out. Check device settings and try again.';
            }, { enableHighAccuracy: true, maximumAge: 0, timeout: 15000 });
        } catch {
            stop(); status.textContent = 'Location is unavailable. Check device settings and try again.';
        }
    });

    use.addEventListener('click', () => {
        if (!best) return;
        const selected = best;
        if (isBoundary()) {
            dismiss();
            status.textContent = '';
            apply(selected, true);
        } else if (apply(selected, false) !== false) dismiss();
    });
    cancel.addEventListener('click', dismiss);
    return dismiss;
}
