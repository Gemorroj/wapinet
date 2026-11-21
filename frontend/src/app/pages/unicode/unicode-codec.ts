/**
 * Клиентская замена legacy-конвертера `/unicode`.
 *
 * Отличия от старого поведения:
 * - эмодзи кодируются одним code point, а не парой суррогатов;
 * - ASCII, пробелы и переводы строк остаются как есть;
 * - `Р` в карте похожих латинских букв заменяется на `P`.
 */
import { escapeHtml } from '../text-tools/text-tools-codec';

export type UnicodeEncodeFormat = 'html' | 'html-named' | 'js' | 'js-surrogate' | 'css' | 'utf8';
export type UnicodeDecodeFormat = 'auto' | 'html' | 'js' | 'css' | 'codepoint';

export interface UnicodeEncodeOptions {
    /** Заменить похожие кириллические буквы латинскими. */
    readonly latin?: boolean;
    /** Убрать ведущие нули из HTML-кода (`&#x0430;` → `&#x430;`). */
    readonly trimLeadingZeros?: boolean;
    /** Экранировать спецсимволы HTML в режимах HTML. */
    readonly escapeHtml?: boolean;
    /** Формат результата. По умолчанию — HTML numeric. */
    readonly format?: UnicodeEncodeFormat;
}

export interface UnicodeCharacter {
    readonly symbol: string;
    readonly codePoints: readonly string[];
    readonly html: string;
    readonly named: string;
    readonly js: string;
    readonly css: string;
    readonly utf8: string;
    readonly utf16: string;
}

export interface UnicodeStats {
    readonly graphemes: number;
    readonly codePoints: number;
    readonly utf16: number;
    readonly utf8: number;
}

const NAMED_ENTITIES: Readonly<Record<number, string>> = {
    34: 'quot',
    38: 'amp',
    39: 'apos',
    60: 'lt',
    62: 'gt',
    160: 'nbsp',
    162: 'cent',
    163: 'pound',
    165: 'yen',
    167: 'sect',
    169: 'copy',
    171: 'laquo',
    174: 'reg',
    176: 'deg',
    177: 'plusmn',
    178: 'sup2',
    181: 'micro',
    182: 'para',
    183: 'middot',
    187: 'raquo',
    189: 'frac12',
    215: 'times',
    247: 'divide',
    8211: 'ndash',
    8212: 'mdash',
    8216: 'lsquo',
    8217: 'rsquo',
    8218: 'sbquo',
    8220: 'ldquo',
    8221: 'rdquo',
    8222: 'bdquo',
    8224: 'dagger',
    8225: 'Dagger',
    8226: 'bull',
    8230: 'hellip',
    8240: 'permil',
    8249: 'lsaquo',
    8250: 'rsaquo',
    8254: 'oline',
    8260: 'frasl',
    8364: 'euro',
    8482: 'trade',
    8592: 'larr',
    8593: 'uarr',
    8594: 'rarr',
    8595: 'darr',
    8596: 'harr',
    8656: 'lArr',
    8657: 'uArr',
    8658: 'rArr',
    8659: 'dArr',
    8660: 'hArr',
    8704: 'forall',
    8706: 'part',
    8707: 'exist',
    8709: 'empty',
    8711: 'nabla',
    8712: 'isin',
    8713: 'notin',
    8715: 'ni',
    8719: 'prod',
    8721: 'sum',
    8722: 'minus',
    8727: 'lowast',
    8730: 'radic',
    8733: 'prop',
    8734: 'infin',
    8736: 'ang',
    8743: 'and',
    8744: 'or',
    8745: 'cap',
    8746: 'cup',
    8747: 'int',
    8756: 'there4',
    8764: 'sim',
    8773: 'cong',
    8776: 'asymp',
    8800: 'ne',
    8801: 'equiv',
    8804: 'le',
    8805: 'ge',
    8834: 'sub',
    8835: 'sup',
    8836: 'nsub',
    8838: 'sube',
    8839: 'supe',
    8853: 'oplus',
    8855: 'otimes',
    8869: 'perp',
    8901: 'sdot',
};

const NAMED_ENTITY_CODES: Readonly<Record<string, number>> = Object.fromEntries(Object.entries(NAMED_ENTITIES).map(([code, name]) => [name, Number(code)]));

const LATIN_LOOKALIKES: Readonly<Record<string, string>> = {
    А: 'A',
    В: 'B',
    Е: 'E',
    К: 'K',
    М: 'M',
    Н: 'H',
    О: 'O',
    Р: 'P',
    С: 'C',
    Т: 'T',
    Х: 'X',
    а: 'a',
    е: 'e',
    о: 'o',
    р: 'p',
    с: 'c',
    у: 'y',
    х: 'x',
};

interface SegmenterLike {
    segment(input: string): Iterable<{ readonly segment: string }>;
}

type SegmenterConstructor = new (locales?: string | string[], options?: { readonly granularity: 'grapheme' }) => SegmenterLike;

const Segmenter = (Intl as unknown as { readonly Segmenter?: SegmenterConstructor }).Segmenter;

export function latinLookalikes(text: string): string {
    return text.replace(/[АВЕКМНОРСТХаеорсух]/g, (char) => LATIN_LOOKALIKES[char] ?? char);
}

export function encodeUnicode(text: string, options: UnicodeEncodeOptions = {}): string {
    let value = text;

    if (options.latin) {
        value = latinLookalikes(value);
    }

    const format = options.format ?? 'html';

    if ('utf8' === format) {
        return utf8Bytes(value);
    }

    if ('js' === format || 'js-surrogate' === format) {
        return encodeJavaScript(value, 'js-surrogate' === format);
    }

    if ('css' === format) {
        return encodeCss(value);
    }

    return encodeHtml(value, options, 'html-named' === format);
}

export function decodeUnicode(text: string, format: UnicodeDecodeFormat = 'auto'): string {
    let value = text;

    if ('auto' === format || 'html' === format) {
        value = decodeHtml(value);
    }

    if ('auto' === format || 'js' === format) {
        value = decodeJavaScript(value);
    }

    if ('auto' === format || 'css' === format) {
        value = decodeCss(value);
    }

    if ('auto' === format || 'codepoint' === format) {
        value = decodeCodePoints(value);
    }

    return value;
}

export function graphemes(text: string): string[] {
    if (Segmenter) {
        return Array.from(new Segmenter(undefined, { granularity: 'grapheme' }).segment(text), (part) => part.segment);
    }

    return Array.from(text);
}

export function textStats(text: string): UnicodeStats {
    const codePoints = Array.from(text);

    return {
        graphemes: graphemes(text).length,
        codePoints: codePoints.length,
        utf16: text.length,
        utf8: new TextEncoder().encode(text).length,
    };
}

export function inspectCharacter(text: string): UnicodeCharacter | null {
    const first = graphemes(text)[0];

    return first ? inspectGrapheme(first) : null;
}

export function inspectGrapheme(symbol: string): UnicodeCharacter {
    return {
        symbol,
        codePoints: Array.from(symbol).map(codePointLabel),
        html: encodeUnicode(symbol, { format: 'html' }),
        named: encodeUnicode(symbol, { format: 'html-named' }),
        js: encodeUnicode(symbol, { format: 'js' }),
        css: encodeUnicode(symbol, { format: 'css' }),
        utf8: utf8Bytes(symbol),
        utf16: Array.from({ length: symbol.length }, (_, index) => symbol.charCodeAt(index).toString(16).padStart(4, '0')).join(' '),
    };
}

/** Отображение `U+XXXX` для одного code point. */
export function codePointLabel(symbol: string): string {
    const codePoint = symbol.codePointAt(0);

    return undefined === codePoint ? '' : `U+${codePoint.toString(16).toUpperCase().padStart(4, '0')}`;
}

/** Отображение всех code points строки, включая ZWJ-последовательности. */
export function codePointsLabel(symbol: string): string {
    return Array.from(symbol).map(codePointLabel).join(' ');
}

function encodeHtml(text: string, options: UnicodeEncodeOptions, named: boolean): string {
    let result = '';

    for (const char of text) {
        const codePoint = char.codePointAt(0) ?? 0;

        if (codePoint <= 0x7e) {
            const name = named ? NAMED_ENTITIES[codePoint] : undefined;

            if (options.escapeHtml && name) {
                result += `&${name};`;
            } else if (options.escapeHtml) {
                result += escapeHtml(char);
            } else {
                result += char;
            }

            continue;
        }

        const name = named ? NAMED_ENTITIES[codePoint] : undefined;

        if (name) {
            result += `&${name};`;
            continue;
        }

        let hex = codePoint.toString(16);

        if (codePoint <= 0xffff) {
            hex = hex.padStart(4, '0');
        }

        if (options.trimLeadingZeros) {
            hex = hex.replace(/^0+/, '') || '0';
        }

        result += `&#x${hex};`;
    }

    return result;
}

function encodeJavaScript(text: string, surrogate: boolean): string {
    let result = '';

    for (const char of text) {
        const codePoint = char.codePointAt(0) ?? 0;

        if (0x5c === codePoint) {
            result += '\\\\';
            continue;
        }

        if (0x0a === codePoint) {
            result += '\\n';
            continue;
        }

        if (0x0d === codePoint) {
            result += '\\r';
            continue;
        }

        if (0x09 === codePoint) {
            result += '\\t';
            continue;
        }

        if (codePoint >= 0x20 && codePoint <= 0x7e) {
            result += char;
            continue;
        }

        if (codePoint > 0xffff && surrogate) {
            const value = codePoint - 0x10000;

            result += `\\u${(0xd800 + (value >> 10)).toString(16).padStart(4, '0')}`;
            result += `\\u${(0xdc00 + (value & 0x3ff)).toString(16).padStart(4, '0')}`;
            continue;
        }

        result += codePoint > 0xffff ? `\\u{${codePoint.toString(16)}}` : `\\u${codePoint.toString(16).padStart(4, '0')}`;
    }

    return result;
}

function encodeCss(text: string): string {
    let result = '';

    for (const char of text) {
        const codePoint = char.codePointAt(0) ?? 0;

        if (0x5c === codePoint) {
            result += '\\\\';
            continue;
        }

        if (codePoint >= 0x20 && codePoint <= 0x7e) {
            result += char;
            continue;
        }

        result += `\\${codePoint.toString(16)} `;
    }

    return result;
}

function utf8Bytes(text: string): string {
    return Array.from(new TextEncoder().encode(text), (byte) => byte.toString(16).padStart(2, '0').toUpperCase()).join(' ');
}

function decodeHtml(text: string): string {
    return text
        .replace(/&#(x?)([0-9a-f]+);/gi, (match, hex: string, digits: string) => {
            const codePoint = Number.parseInt(digits, hex ? 16 : 10);

            return isValidCodePoint(codePoint) ? String.fromCodePoint(codePoint) : match;
        })
        .replace(/&([a-z][a-z0-9]+);/gi, (match, name: string) => {
            const codePoint = NAMED_ENTITY_CODES[name] ?? NAMED_ENTITY_CODES[name.toLowerCase()];

            return undefined === codePoint ? match : String.fromCodePoint(codePoint);
        });
}

function decodeJavaScript(text: string): string {
    return text.replace(/\\u\{([0-9a-f]{1,6})\}|\\u([0-9a-f]{4})/gi, (match, long: string | undefined, short: string | undefined) => {
        const codePoint = Number.parseInt(long ?? short ?? '', 16);

        if (codePoint >= 0xd800 && codePoint <= 0xdfff) {
            return String.fromCharCode(codePoint);
        }

        return isValidCodePoint(codePoint) ? String.fromCodePoint(codePoint) : match;
    });
}

function decodeCss(text: string): string {
    return text.replace(/\\([0-9a-f]{1,6})\s?/gi, (match, digits: string) => {
        const codePoint = Number.parseInt(digits, 16);

        return isValidCodePoint(codePoint) ? String.fromCodePoint(codePoint) : match;
    });
}

function decodeCodePoints(text: string): string {
    return text.replace(/\bU\+([0-9a-f]{1,6})\b/gi, (match, digits: string) => {
        const codePoint = Number.parseInt(digits, 16);

        return isValidCodePoint(codePoint) ? String.fromCodePoint(codePoint) : match;
    });
}

function isValidCodePoint(codePoint: number): boolean {
    return Number.isInteger(codePoint) && codePoint >= 0 && codePoint <= 0x10ffff && !(codePoint >= 0xd800 && codePoint <= 0xdfff);
}
