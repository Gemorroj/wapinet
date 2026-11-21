/**
 * Formats a UTC offset for display. The input is the value of Date#getTimezoneOffset(),
 * which is inverted: Moscow returns -180 for UTC+03:00.
 */
export function utcOffsetLabel(timezoneOffset: number): string {
    const total = -timezoneOffset;
    const sign = total < 0 ? '-' : '+';
    const absolute = Math.abs(total);
    const hours = String(Math.floor(absolute / 60)).padStart(2, '0');
    const minutes = String(absolute % 60).padStart(2, '0');

    return `UTC${sign}${hours}:${minutes}`;
}

export function utcOffsetNow(): string {
    return utcOffsetLabel(new Date().getTimezoneOffset());
}
