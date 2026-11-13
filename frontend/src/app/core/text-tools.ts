export type TextToolGroupId = 'encoding' | 'data' | 'text';

export interface TextToolGroup {
    readonly id: TextToolGroupId;
    readonly label: string;
}

export interface TextToolOperation {
    readonly id: string;
    readonly group: TextToolGroupId;
    readonly label: string;
    readonly hint?: string;
    readonly run: (input: string) => string;
}

export class TextToolError extends Error {}

export const TEXT_TOOL_GROUPS: readonly TextToolGroup[] = [
    { id: 'encoding', label: 'Кодирование' },
    { id: 'data', label: 'Данные' },
    { id: 'text', label: 'Текст' },
];

export const TEXT_TOOL_OPERATIONS: readonly TextToolOperation[] = [
    // Encoding
    { id: 'base64-encode', group: 'encoding', label: 'Base64 encode', run: base64Encode },
    { id: 'base64-decode', group: 'encoding', label: 'Base64 decode', run: base64Decode },
    { id: 'base64url-encode', group: 'encoding', label: 'Base64 URL-safe encode', run: base64UrlEncode },
    { id: 'base64url-decode', group: 'encoding', label: 'Base64 URL-safe decode', run: base64UrlDecode },
    { id: 'url-encode', group: 'encoding', label: 'URL encode', run: encodeURIComponent },
    { id: 'url-decode', group: 'encoding', label: 'URL decode', run: urlDecode },
    { id: 'hex-encode', group: 'encoding', label: 'Hex encode', run: hexEncode },
    { id: 'hex-decode', group: 'encoding', label: 'Hex decode', run: hexDecode },
    { id: 'html-escape', group: 'encoding', label: 'HTML escape', run: escapeHtml },
    { id: 'html-unescape', group: 'encoding', label: 'HTML unescape', run: unescapeHtml },

    // Data
    { id: 'json-format', group: 'data', label: 'JSON форматировать', run: jsonFormat },
    { id: 'json-minify', group: 'data', label: 'JSON минифицировать', run: jsonMinify },
    {
        id: 'json-escape',
        group: 'data',
        label: 'JSON escape строки',
        hint: 'Результат — JSON-строка в кавычках.',
        run: jsonEscape,
    },
    {
        id: 'json-unescape',
        group: 'data',
        label: 'JSON unescape строки',
        hint: 'Ожидается JSON-строка в кавычках.',
        run: jsonUnescape,
    },
    { id: 'query-to-json', group: 'data', label: 'Query string → JSON', run: queryToJson },
    { id: 'json-to-query', group: 'data', label: 'JSON → query string', run: jsonToQuery },
    { id: 'url-parse', group: 'data', label: 'URL → компоненты', run: urlParse },

    // Text
    { id: 'lower', group: 'text', label: 'нижний регистр', run: (input) => input.toLocaleLowerCase() },
    { id: 'upper', group: 'text', label: 'ВЕРХНИЙ РЕГИСТР', run: (input) => input.toLocaleUpperCase() },
    { id: 'title', group: 'text', label: 'Title Case', run: titleCase },
    { id: 'camel', group: 'text', label: 'camelCase', run: camelCase },
    { id: 'pascal', group: 'text', label: 'PascalCase', run: pascalCase },
    { id: 'snake', group: 'text', label: 'snake_case', run: snakeCase },
    { id: 'kebab', group: 'text', label: 'kebab-case', run: kebabCase },
    { id: 'constant', group: 'text', label: 'CONSTANT_CASE', run: constantCase },
    { id: 'lines-sort', group: 'text', label: 'Сортировать строки', run: sortLines },
    { id: 'lines-unique', group: 'text', label: 'Удалить дубли строк', run: uniqueLines },
    { id: 'lines-trim', group: 'text', label: 'Обрезать пробелы', run: trimLines },
    { id: 'lines-remove-empty', group: 'text', label: 'Удалить пустые строки', run: removeEmptyLines },
    { id: 'lines-reverse', group: 'text', label: 'Обратный порядок строк', run: reverseLines },
];

export function convertText(id: string, input: string): string {
    const operation = TEXT_TOOL_OPERATIONS.find((item) => item.id === id);

    if (!operation) {
        throw new TextToolError('Неизвестная операция.');
    }

    try {
        return operation.run(input);
    } catch (error) {
        if (error instanceof TextToolError) {
            throw error;
        }

        throw new TextToolError(error instanceof Error ? error.message : 'Не удалось преобразовать данные.');
    }
}

function base64Encode(input: string): string {
    return btoa(bytesToBinary(new TextEncoder().encode(input)));
}

function base64Decode(input: string): string {
    try {
        return decodeUtf8(binaryToBytes(atob(input.replace(/\s+/g, ''))));
    } catch (error) {
        if (error instanceof TextToolError) {
            throw error;
        }

        throw new TextToolError('Некорректный Base64.');
    }
}

function base64UrlEncode(input: string): string {
    return base64Encode(input).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function base64UrlDecode(input: string): string {
    const normalized = input.replace(/-/g, '+').replace(/_/g, '/').replace(/\s+/g, '');
    const padding = '='.repeat((4 - (normalized.length % 4)) % 4);

    return base64Decode(normalized + padding);
}

function urlDecode(input: string): string {
    try {
        return decodeURIComponent(input);
    } catch {
        throw new TextToolError('Некорректная URL-последовательность.');
    }
}

function hexEncode(input: string): string {
    return Array.from(new TextEncoder().encode(input), (byte) => byte.toString(16).padStart(2, '0')).join('');
}

function hexDecode(input: string): string {
    const normalized = input.replace(/0x/gi, '').replace(/[\s:,-]/g, '');

    if ('' !== normalized && !/^[0-9a-f]+$/i.test(normalized)) {
        throw new TextToolError('Hex содержит недопустимые символы.');
    }

    if (0 !== normalized.length % 2) {
        throw new TextToolError('Hex должен содержать чётное число символов.');
    }

    const bytes = new Uint8Array(normalized.length / 2);

    for (let index = 0; index < bytes.length; index++) {
        bytes[index] = Number.parseInt(normalized.slice(index * 2, index * 2 + 2), 16);
    }

    return decodeUtf8(bytes);
}

const HTML_ESCAPES: Readonly<Record<string, string>> = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
};

export function escapeHtml(input: string): string {
    return input.replace(/[&<>"']/g, (char) => HTML_ESCAPES[char] ?? char);
}

function unescapeHtml(input: string): string {
    if ('undefined' === typeof document) {
        throw new TextToolError('HTML unescape доступен только в браузере.');
    }

    const textarea = document.createElement('textarea');
    textarea.innerHTML = input;

    return textarea.value;
}

function jsonFormat(input: string): string {
    return JSON.stringify(parseJson(input), null, 2);
}

function jsonMinify(input: string): string {
    return JSON.stringify(parseJson(input));
}

function jsonEscape(input: string): string {
    return JSON.stringify(input);
}

function jsonUnescape(input: string): string {
    const value = parseJson(input);

    if ('string' !== typeof value) {
        throw new TextToolError('Ожидается JSON-строка в кавычках.');
    }

    return value;
}

function queryToJson(input: string): string {
    const params = new URLSearchParams(input.replace(/^\?/, ''));
    const result: Record<string, string | string[]> = {};

    for (const [key, value] of params) {
        const current = result[key];

        if (undefined === current) {
            result[key] = value;
        } else if (Array.isArray(current)) {
            current.push(value);
        } else {
            result[key] = [current, value];
        }
    }

    return JSON.stringify(result, null, 2);
}

function jsonToQuery(input: string): string {
    const value = parseJson(input);

    if (!isRecord(value)) {
        throw new TextToolError('Ожидается JSON-объект.');
    }

    const params = new URLSearchParams();

    for (const [key, item] of Object.entries(value)) {
        const values = Array.isArray(item) ? item : [item];

        for (const entry of values) {
            params.append(key, queryValue(entry));
        }
    }

    return params.toString();
}

function urlParse(input: string): string {
    try {
        const url = new URL(input);

        return JSON.stringify(
            {
                href: url.href,
                origin: url.origin,
                protocol: url.protocol,
                username: url.username,
                host: url.host,
                hostname: url.hostname,
                port: url.port,
                pathname: url.pathname,
                search: url.search,
                hash: url.hash,
            },
            null,
            2,
        );
    } catch {
        throw new TextToolError('Некорректный URL. Укажите адрес со схемой, например https://example.com/path.');
    }
}

function titleCase(input: string): string {
    return words(input).map(capitalize).join(' ');
}

function camelCase(input: string): string {
    const parts = words(input);
    const [first = '', ...rest] = parts;

    return first.toLocaleLowerCase() + rest.map(capitalize).join('');
}

function pascalCase(input: string): string {
    return words(input).map(capitalize).join('');
}

function snakeCase(input: string): string {
    return words(input)
        .map((word) => word.toLocaleLowerCase())
        .join('_');
}

function kebabCase(input: string): string {
    return words(input)
        .map((word) => word.toLocaleLowerCase())
        .join('-');
}

function constantCase(input: string): string {
    return words(input)
        .map((word) => word.toLocaleUpperCase())
        .join('_');
}

function sortLines(input: string): string {
    return normalizeLines(input)
        .sort((first, second) => first.localeCompare(second, 'ru'))
        .join('\n');
}

function uniqueLines(input: string): string {
    return Array.from(new Set(normalizeLines(input))).join('\n');
}

function trimLines(input: string): string {
    return normalizeLines(input)
        .map((line) => line.trim())
        .join('\n');
}

function removeEmptyLines(input: string): string {
    return normalizeLines(input)
        .filter((line) => '' !== line.trim())
        .join('\n');
}

function reverseLines(input: string): string {
    return normalizeLines(input).reverse().join('\n');
}

function parseJson(input: string): unknown {
    try {
        return JSON.parse(input);
    } catch (error) {
        throw new TextToolError(`Некорректный JSON: ${error instanceof Error ? error.message : 'не удалось разобрать.'}`);
    }
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return 'object' === typeof value && null !== value && !Array.isArray(value);
}

function queryValue(value: unknown): string {
    if (null === value || undefined === value) {
        return '';
    }

    if (isRecord(value) || Array.isArray(value)) {
        throw new TextToolError('Вложенные объекты и массивы в query string не поддерживаются.');
    }

    return String(value);
}

function normalizeLines(input: string): string[] {
    return input.replace(/\r\n?/g, '\n').split('\n');
}

function words(input: string): string[] {
    return input
        .replace(/(\p{Ll}|\p{N})(\p{Lu})/gu, '$1 $2')
        .replace(/(\p{Lu}+)(\p{Lu}\p{Ll})/gu, '$1 $2')
        .split(/[^\p{L}\p{N}]+/u)
        .filter(Boolean);
}

function capitalize(word: string): string {
    const [first = '', ...rest] = Array.from(word.toLocaleLowerCase());

    return first.toLocaleUpperCase() + rest.join('');
}

function bytesToBinary(bytes: Uint8Array): string {
    let binary = '';

    for (const byte of bytes) {
        binary += String.fromCharCode(byte);
    }

    return binary;
}

function binaryToBytes(binary: string): Uint8Array {
    return Uint8Array.from(binary, (character) => character.charCodeAt(0));
}

function decodeUtf8(bytes: Uint8Array): string {
    try {
        return new TextDecoder('utf-8', { fatal: true }).decode(bytes);
    } catch {
        throw new TextToolError('Данные не являются корректным UTF-8.');
    }
}
