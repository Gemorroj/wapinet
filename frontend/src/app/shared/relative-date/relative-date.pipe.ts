import { Pipe, PipeTransform } from '@angular/core';

export type RelativeDateMode = 'date' | 'datetime';

const DAY = 24 * 60 * 60 * 1000;

@Pipe({ name: 'relativeDate' })
export class RelativeDatePipe implements PipeTransform {
    transform(value: string | Date | null | undefined, mode: RelativeDateMode = 'datetime', now: Date = new Date()): string {
        if (!value) {
            return '';
        }

        const date = value instanceof Date ? value : new Date(value);

        if (Number.isNaN(date.getTime())) {
            return '';
        }

        const time = `${this.pad(date.getHours())}:${this.pad(date.getMinutes())}`;
        const label = this.relativeLabel(date, now);

        if (null !== label) {
            return 'date' === mode ? label : `${label} в ${time}`;
        }

        const formatted = `${this.pad(date.getDate())}.${this.pad(date.getMonth() + 1)}.${date.getFullYear()}`;

        return 'date' === mode ? formatted : `${formatted} ${time}`;
    }

    private relativeLabel(date: Date, now: Date): string | null {
        const difference = Math.round((this.startOfDay(date) - this.startOfDay(now)) / DAY);

        return matchDay(difference);
    }

    private startOfDay(date: Date): number {
        return new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();
    }

    private pad(value: number): string {
        return String(value).padStart(2, '0');
    }
}

function matchDay(difference: number): string | null {
    switch (difference) {
        case 0:
            return 'Сегодня';
        case -1:
            return 'Вчера';
        case -2:
            return 'Позавчера';
        case 1:
            return 'Завтра';
        case 2:
            return 'Послезавтра';
        default:
            return null;
    }
}
