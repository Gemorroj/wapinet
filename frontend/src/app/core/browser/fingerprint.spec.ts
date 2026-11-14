import { hashString } from './fingerprint';

describe('hashString', () => {
    it('returns a stable hash', () => {
        expect(hashString('wapinet')).toBe(hashString('wapinet'));
    });

    it('returns different hashes for different values', () => {
        expect(hashString('canvas')).not.toBe(hashString('audio'));
    });

    it('returns 16 hex characters', () => {
        expect(hashString('test')).toMatch(/^[0-9a-f]{16}$/);
    });
});
