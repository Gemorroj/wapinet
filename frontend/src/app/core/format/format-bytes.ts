const UNITS = ['КБ', 'МБ', 'ГБ'];

export function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} Б`;
    }

    let value = bytes / 1024;
    let unit = 0;

    while (value >= 1024 && unit < UNITS.length - 1) {
        value /= 1024;
        unit++;
    }

    return `${value.toFixed(value >= 10 ? 0 : 1)} ${UNITS[unit]}`;
}
