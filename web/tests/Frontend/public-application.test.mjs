import assert from 'node:assert/strict';
import test from 'node:test';
import { countApplicationWords } from '../../resources/js/pages/public-application.js';

// Check the same boundary shown to applicants while they type.
test('the reason counter handles empty input, line breaks, and the 500-word boundary', () => {
    assert.equal(countApplicationWords('  \n '), 0);
    assert.equal(countApplicationWords('fishing  community\nmember'), 3);
    assert.equal(countApplicationWords(Array(500).fill('word').join(' ')), 500);
    assert.equal(countApplicationWords(Array(501).fill('word').join(' ')), 501);
});
