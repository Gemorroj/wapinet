import { utcOffsetLabel } from './clock';

describe('utcOffsetLabel', () => {
    it('formats a positive offset from getTimezoneOffset', () => {
        expect(utcOffsetLabel(-180)).toBe('UTC+03:00');
    });

    it('formats UTC itself', () => {
        expect(utcOffsetLabel(0)).toBe('UTC+00:00');
    });

    it('formats a negative offset', () => {
        expect(utcOffsetLabel(330)).toBe('UTC-05:30');
    });

    it('formats offsets with a 45 minute part', () => {
        expect(utcOffsetLabel(-345)).toBe('UTC+05:45');
    });
});
