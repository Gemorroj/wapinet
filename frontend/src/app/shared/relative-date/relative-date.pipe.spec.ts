import { RelativeDatePipe } from './relative-date.pipe';

describe('RelativeDatePipe', () => {
    const pipe = new RelativeDatePipe();
    const now = new Date(2026, 10, 5, 18, 0);

    it('shows today with time', () => {
        expect(pipe.transform(new Date(2026, 10, 5, 16, 34), 'datetime', now)).toBe('Сегодня в 16:34');
    });

    it('shows yesterday and the day before yesterday', () => {
        expect(pipe.transform(new Date(2026, 10, 4, 9, 5), 'datetime', now)).toBe('Вчера в 09:05');
        expect(pipe.transform(new Date(2026, 10, 3, 23, 59), 'datetime', now)).toBe('Позавчера в 23:59');
    });

    it('shows tomorrow and the day after tomorrow', () => {
        expect(pipe.transform(new Date(2026, 10, 6, 8, 0), 'datetime', now)).toBe('Завтра в 08:00');
        expect(pipe.transform(new Date(2026, 10, 7, 8, 0), 'datetime', now)).toBe('Послезавтра в 08:00');
    });

    it('falls back to the absolute date', () => {
        const date = new Date(2020, 0, 2, 3, 4);

        expect(pipe.transform(date, 'datetime', now)).toBe('02.01.2020 03:04');
        expect(pipe.transform(date, 'date', now)).toBe('02.01.2020');
        expect(pipe.transform(new Date(2026, 10, 5, 16, 34), 'date', now)).toBe('Сегодня');
    });

    it('handles empty and invalid values', () => {
        expect(pipe.transform(null, 'datetime', now)).toBe('');
        expect(pipe.transform('', 'datetime', now)).toBe('');
        expect(pipe.transform('not-a-date', 'datetime', now)).toBe('');
    });
});
