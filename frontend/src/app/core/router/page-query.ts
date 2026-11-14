import { ParamMap } from '@angular/router';

/**
 * Reads the 1-based page number from the query string. A missing, invalid or
 * out-of-range value falls back to the first page.
 */
export function readPage(params: ParamMap): number {
    const page = Number(params.get('page') ?? 1);

    return Number.isFinite(page) && page >= 1 ? Math.floor(page) : 1;
}
