import { HttpErrorResponse } from '@angular/common/http';

export function describeError(error: unknown): string {
    if (error instanceof HttpErrorResponse) {
        if (429 === error.status) {
            const retryAfter = retryAfterSeconds(error);

            return retryAfter ? `Слишком много запросов. Попробуйте через ${describeWait(retryAfter)}.` : 'Слишком много запросов. Попробуйте позже.';
        }

        const payload = error.error as unknown;

        if (payload && 'object' === typeof payload) {
            const detail = (payload as { detail?: unknown }).detail;

            if ('string' === typeof detail && '' !== detail) {
                return detail;
            }
        }

        if (0 === error.status) {
            return 'Не удалось выполнить запрос. Проверьте соединение.';
        }

        if (403 === error.status) {
            return 'Доступ запрещён.';
        }

        if (404 === error.status) {
            return 'Не найдено.';
        }

        if (413 === error.status) {
            return 'Файл слишком большой. Уменьшите размер или загрузите его частями.';
        }

        return `Ошибка запроса (${error.status}).`;
    }

    return error instanceof Error ? error.message : 'Неизвестная ошибка.';
}

function retryAfterSeconds(error: HttpErrorResponse): number | null {
    const value = error.headers.get('Retry-After');

    if (null === value) {
        return null;
    }

    const seconds = Number(value);

    if (Number.isFinite(seconds)) {
        return seconds > 0 ? Math.ceil(seconds) : null;
    }

    const date = Date.parse(value);
    const remaining = Math.ceil((date - Date.now()) / 1000);

    return Number.isNaN(date) || remaining <= 0 ? null : remaining;
}

function describeWait(seconds: number): string {
    if (seconds < 60) {
        return plural(seconds, 'секунду', 'секунды', 'секунд');
    }

    const minutes = Math.ceil(seconds / 60);

    if (minutes < 60) {
        return plural(minutes, 'минуту', 'минуты', 'минут');
    }

    return plural(Math.ceil(minutes / 60), 'час', 'часа', 'часов');
}

function plural(count: number, one: string, few: string, many: string): string {
    const mod100 = count % 100;
    const mod10 = count % 10;

    if (mod100 >= 11 && mod100 <= 14) {
        return `${count} ${many}`;
    }

    if (1 === mod10) {
        return `${count} ${one}`;
    }

    if (mod10 >= 2 && mod10 <= 4) {
        return `${count} ${few}`;
    }

    return `${count} ${many}`;
}
