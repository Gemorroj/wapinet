import { codePointLabel, codePointsLabel, decodeUnicode, encodeUnicode, graphemes, inspectGrapheme, latinLookalikes, textStats } from './unicode-codec';

describe('encodeUnicode', () => {
    it('keeps ascii as is', () => {
        expect(encodeUnicode('Hello, world!')).toBe('Hello, world!');
    });

    it('encodes bitmap characters with four hex digits', () => {
        expect(encodeUnicode('Привет')).toBe('&#x041f;&#x0440;&#x0438;&#x0432;&#x0435;&#x0442;');
    });

    it('encodes astral characters as a single code point', () => {
        expect(encodeUnicode('😀')).toBe('&#x1f600;');
    });

    it('keeps line breaks as is', () => {
        expect(encodeUnicode('а\nб')).toBe('&#x0430;\n&#x0431;');
    });

    it('trims leading zeros only for the padded codes', () => {
        expect(encodeUnicode('¡', { trimLeadingZeros: true })).toBe('&#xa1;');
        expect(encodeUnicode('¡')).toBe('&#x00a1;');
    });

    it('escapes html before encoding', () => {
        expect(encodeUnicode('<a & b>', { escapeHtml: true })).toBe('&lt;a &amp; b&gt;');
        expect(encodeUnicode('<a & b>')).toBe('<a & b>');
    });

    it('replaces cyrillic lookalikes with latin letters', () => {
        expect(latinLookalikes('Россия')).toBe('Poccия');
        expect(encodeUnicode('АВ', { latin: true })).toBe('AB');
        expect(encodeUnicode('АВ')).toBe('&#x0410;&#x0412;');
    });

    it('applies html escaping before latin substitution', () => {
        expect(encodeUnicode('"Р"', { escapeHtml: true, latin: true })).toBe('&quot;P&quot;');
    });
});

describe('codePointLabel', () => {
    it('renders an uppercase code point', () => {
        expect(codePointLabel('←')).toBe('U+2190');
        expect(codePointLabel('😀')).toBe('U+1F600');
    });

    it('renders every code point of a grapheme', () => {
        expect(codePointsLabel('😀')).toBe('U+1F600');
        expect(codePointsLabel('👨‍👩‍👧‍👦')).toContain('U+200D');
    });
});

describe('formats', () => {
    it('uses named html entities where possible', () => {
        expect(encodeUnicode('©', { format: 'html-named' })).toBe('&copy;');
        expect(encodeUnicode('←', { format: 'html-named' })).toBe('&larr;');
        expect(encodeUnicode('😀', { format: 'html-named' })).toBe('&#x1f600;');
    });

    it('encodes javascript escapes', () => {
        expect(encodeUnicode('😀', { format: 'js' })).toBe('\\u{1f600}');
        expect(encodeUnicode('😀', { format: 'js-surrogate' })).toBe('\\ud83d\\ude00');
        expect(encodeUnicode('Привет', { format: 'js' })).toContain('\\u041f');
    });

    it('encodes css escapes', () => {
        expect(encodeUnicode('😀', { format: 'css' })).toBe('\\1f600 ');
    });

    it('encodes utf-8 bytes', () => {
        expect(encodeUnicode('A', { format: 'utf8' })).toBe('41');
        expect(encodeUnicode('😀', { format: 'utf8' })).toBe('F0 9F 98 80');
    });
});

describe('decodeUnicode', () => {
    it('decodes numeric and named html entities', () => {
        expect(decodeUnicode('&#x041f;&#x0440;&#x0438;')).toBe('При');
        expect(decodeUnicode('&#1055;&#1088;&#1080;')).toBe('При');
        expect(decodeUnicode('&copy; &amp;')).toBe('© &');
    });

    it('decodes javascript escapes', () => {
        expect(decodeUnicode('\\u{1F600}')).toBe('😀');
        expect(decodeUnicode('\\uD83D\\uDE00')).toBe('😀');
    });

    it('decodes css escapes', () => {
        expect(decodeUnicode('\\1F600 ')).toBe('😀');
    });

    it('decodes code point notation', () => {
        expect(decodeUnicode('U+1F600')).toBe('😀');
    });

    it('leaves unknown entities unchanged', () => {
        expect(decodeUnicode('&unknown;')).toBe('&unknown;');
    });
});

describe('inspect', () => {
    it('describes an astral character', () => {
        const info = inspectGrapheme('😀');

        expect(info.codePoints).toEqual(['U+1F600']);
        expect(info.html).toBe('&#x1f600;');
        expect(info.utf8).toBe('F0 9F 98 80');
        expect(info.utf16).toBe('d83d de00');
    });

    it('counts graphemes, code points and encodings', () => {
        expect(textStats('😀')).toEqual({ graphemes: 1, codePoints: 1, utf16: 2, utf8: 4 });
        expect(textStats('e\u0301')).toEqual({ graphemes: 1, codePoints: 2, utf16: 2, utf8: 3 });
    });

    it('keeps a zwj emoji as one grapheme', () => {
        expect(graphemes('👨‍👩‍👧‍👦')).toHaveLength(1);
        expect(inspectGrapheme('👨‍👩‍👧‍👦').codePoints.length).toBeGreaterThan(1);
    });
});
