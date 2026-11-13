import { convertToParamMap } from '@angular/router';

import { readPage } from './page-query';

describe('readPage', () => {
    it('returns the first page for missing or invalid values', () => {
        expect(readPage(convertToParamMap({}))).toBe(1);
        expect(readPage(convertToParamMap({ page: '' }))).toBe(1);
        expect(readPage(convertToParamMap({ page: 'abc' }))).toBe(1);
        expect(readPage(convertToParamMap({ page: '0' }))).toBe(1);
    });

    it('parses a positive page number', () => {
        expect(readPage(convertToParamMap({ page: '3' }))).toBe(3);
        expect(readPage(convertToParamMap({ page: '4.9' }))).toBe(4);
    });
});
