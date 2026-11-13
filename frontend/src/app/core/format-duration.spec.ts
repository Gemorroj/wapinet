import { formatDuration } from './format-duration';

describe('formatDuration', () => {
    it('formats minutes and seconds', () => {
        expect(formatDuration(5)).toBe('0:05');
        expect(formatDuration(125)).toBe('2:05');
    });

    it('formats hours', () => {
        expect(formatDuration(3725)).toBe('1:02:05');
    });
});
