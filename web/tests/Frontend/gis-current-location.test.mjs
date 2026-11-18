import test from 'node:test';
import assert from 'node:assert/strict';
import { initCurrentLocation } from '../../resources/js/pages/gis-current-location.js';

function fixture(boundary = false) {
    const element = () => ({ hidden: false, disabled: false, textContent: '', addEventListener(event, fn) { this[event] = fn; }, click() { this.clickHandler?.(); } });
    const button = element(), panel = element(), status = element(), use = element(), cancel = element();
    let success, failure, options, cleared = 0, applied = [], previews = [];
    const geo = { watchPosition(ok, fail, opts) { success = ok; failure = fail; options = opts; return 7; }, clearWatch(id) { assert.equal(id, 7); cleared++; } };
    const dismiss = initCurrentLocation({ button, panel, status, use, cancel, geolocation: geo,
        isBoundary: () => boundary, preview: (value, first) => previews.push({ value, first }), clearPreview() {},
        apply: (value, isBoundary) => { applied.push({ value, isBoundary }); } });
    return { button, panel, status, use, cancel, dismiss, send: (coords, timestamp = Date.now()) => success({ coords, timestamp }),
        fail: code => failure({ code }), get options() { return options; }, get cleared() { return cleared; }, applied, previews };
}

test('requests fresh high accuracy and uses the best reading only after confirmation', () => {
    const f = fixture(); f.button.click();
    assert.deepEqual(f.options, { enableHighAccuracy: true, maximumAge: 0, timeout: 15000 });
    f.send({ latitude: 10, longitude: 123, accuracy: 30 });
    f.send({ latitude: 11, longitude: 124, accuracy: 8 });
    f.send({ latitude: 12, longitude: 125, accuracy: 50 });
    assert.equal(f.applied.length, 0); assert.match(f.status.textContent, /±8 m/);
    f.use.click();
    assert.deepEqual(f.applied[0], { value: { latitude: 11, longitude: 124, accuracy: 8 }, isBoundary: false });
    assert.equal(f.cleared, 1); assert.equal(f.panel.hidden, true);
});

test('cancellation stops location updates and ignores late or stale readings', () => {
    const f = fixture(); f.button.click();
    f.send({ latitude: 10, longitude: 123, accuracy: 5 }, Date.now() - 60000);
    assert.equal(f.previews.length, 0);
    f.cancel.click(); f.send({ latitude: 10, longitude: 123, accuracy: 5 });
    assert.equal(f.previews.length, 0); assert.equal(f.cleared, 1); assert.equal(f.applied.length, 0);
});

test('permission denial explains the problem and boundary uses a map-only selection', () => {
    const denied = fixture(); denied.button.click(); denied.fail(1);
    assert.match(denied.status.textContent, /permission was denied/i); assert.equal(denied.cleared, 1);
    const f = fixture(true); f.button.click(); f.send({ latitude: 10, longitude: 123, accuracy: 15 }); f.use.click();
    assert.equal(f.applied[0].isBoundary, true); assert.equal(f.cleared, 1);
});
