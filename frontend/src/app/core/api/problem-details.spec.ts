import { HttpErrorResponse, HttpHeaders } from '@angular/common/http';

import { describeError } from './problem-details';

function rateLimited(retryAfter?: string): HttpErrorResponse {
    return new HttpErrorResponse({
        status: 429,
        headers: retryAfter ? new HttpHeaders({ 'Retry-After': retryAfter }) : undefined,
    });
}

describe('describeError', () => {
    it('prefers the Problem+JSON detail', () => {
        expect(describeError(new HttpErrorResponse({ status: 422, error: { detail: 'Validation failed.' } }))).toBe('Validation failed.');
    });

    it('explains an oversized upload', () => {
        expect(describeError(new HttpErrorResponse({ status: 413 }))).toBe('Файл слишком большой. Уменьшите размер или загрузите его частями.');
    });

    it('handles network errors without a status', () => {
        expect(describeError(new HttpErrorResponse({ status: 0 }))).toBe('Не удалось выполнить запрос. Проверьте соединение.');
    });

    it('localizes common HTTP statuses', () => {
        expect(describeError(new HttpErrorResponse({ status: 403 }))).toBe('Доступ запрещён.');
        expect(describeError(new HttpErrorResponse({ status: 404 }))).toBe('Не найдено.');
    });

    it('falls back to the status code', () => {
        expect(describeError(new HttpErrorResponse({ status: 500 }))).toBe('Ошибка запроса (500).');
    });

    it('formats the retry delay from the Retry-After header', () => {
        expect(describeError(rateLimited('42'))).toBe('Слишком много запросов. Попробуйте через 42 секунды.');
        expect(describeError(rateLimited('1'))).toBe('Слишком много запросов. Попробуйте через 1 секунду.');
        expect(describeError(rateLimited('300'))).toBe('Слишком много запросов. Попробуйте через 5 минут.');
        expect(describeError(rateLimited('7200'))).toBe('Слишком много запросов. Попробуйте через 2 часа.');
    });

    it('supports a Retry-After date', () => {
        const retryAfter = new Date(Date.now() + 180000).toUTCString();

        expect(describeError(rateLimited(retryAfter))).toBe('Слишком много запросов. Попробуйте через 3 минуты.');
    });

    it('falls back when Retry-After is missing or invalid', () => {
        expect(describeError(rateLimited())).toBe('Слишком много запросов. Попробуйте позже.');
        expect(describeError(rateLimited('soon'))).toBe('Слишком много запросов. Попробуйте позже.');
    });
});
