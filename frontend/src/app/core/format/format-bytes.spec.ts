import { formatBytes } from './format-bytes';

describe('formatBytes', () => {
    it('formats bytes', () => {
        expect(formatBytes(0)).toBe('0 Б');
        expect(formatBytes(1023)).toBe('1023 Б');
    });

    it('formats kilobytes and megabytes', () => {
        expect(formatBytes(1024)).toBe('1.0 КБ');
        expect(formatBytes(2.5 * 1024 * 1024)).toBe('2.5 МБ');
        expect(formatBytes(15 * 1024 * 1024)).toBe('15 МБ');
    });
});
