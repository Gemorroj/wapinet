export interface CurlRequest {
    readonly url: string;
    readonly method: string;
    readonly headers: string;
    readonly body: string;
    readonly base64Body: boolean;
    readonly timeout: number;
    readonly httpVersion: 'auto' | '1.1' | '2' | '3';
    readonly verifyTls: boolean;
}

export interface CurlParseResult {
    readonly request: CurlRequest | null;
    readonly warnings: readonly string[];
    readonly error: string | null;
}

/** Флаги, за которыми идёт значение. */
const VALUE_FLAGS = new Set([
    '--url',
    '-X',
    '--request',
    '-H',
    '--header',
    '-d',
    '--data',
    '--data-raw',
    '--data-ascii',
    '--data-urlencode',
    '--data-binary',
    '--json',
    '-F',
    '--form',
    '--form-string',
    '-T',
    '--upload-file',
    '-u',
    '--user',
    '-A',
    '--user-agent',
    '-e',
    '--referer',
    '-b',
    '--cookie',
    '-m',
    '--max-time',
    '--connect-timeout',
    '-o',
    '--output',
    '-w',
    '--write-out',
    '-c',
    '--cookie-jar',
    '--cert',
    '--key',
    '--cacert',
    '--capath',
    '--pass',
    '--proxy',
    '-x',
    '--resolve',
    '--connect-to',
    '--interface',
    '--limit-rate',
    '--retry',
    '--retry-delay',
    '--retry-max-time',
    '--request-target',
    '--url-query',
]);

/**
 * Флаги без значения, которые имеют смысл для запроса
 * либо осознанно игнорируются.
 */
const BOOLEAN_FLAGS = new Set([
    '-s',
    '--silent',
    '-S',
    '--show-error',
    '-v',
    '--verbose',
    '-i',
    '--include',
    '-N',
    '--no-buffer',
    '-f',
    '--fail',
    '--fail-with-body',
    '--no-progress-meter',
    '--progress-bar',
    '--compressed',
    '-k',
    '--insecure',
    '-L',
    '--location',
    '--location-trusted',
    '-G',
    '--get',
    '-I',
    '--head',
    '-O',
    '--remote-name',
    '--path-as-is',
    '--globoff',
    '-q',
    '--disable',
    '-4',
    '--ipv4',
    '-6',
    '--ipv6',
    '--http1.0',
    '--http1.1',
    '--http2',
    '--http2-prior-knowledge',
    '--http3',
    '--tlsv1.2',
    '--tlsv1.3',
]);

/**
 * Разбирает распространённую команду cURL в поля HTTP-инструмента.
 *
 * Это не shell: переменные, подстановки и файловые аргументы не исполняются.
 * Неизвестные и неподдерживаемые флаги возвращаются в `warnings`.
 */
export function parseCurl(command: string): CurlParseResult {
    let tokens: string[];

    try {
        tokens = tokenize(command.trim());
    } catch (error) {
        return {
            request: null,
            warnings: [],
            error: error instanceof Error ? error.message : 'Не удалось разобрать команду cURL.',
        };
    }

    if (tokens.length && /^curl(\.exe)?$/i.test(tokens[0])) {
        tokens.shift();
    }

    if (!tokens.length) {
        return { request: null, warnings: [], error: 'Вставьте команду cURL.' };
    }

    const warnings: string[] = [];
    let url = '';
    let method = '';
    let timeout = 10;
    let httpVersion: CurlRequest['httpVersion'] = 'auto';
    let verifyTls = true;
    let get = false;
    const headers: string[] = [];
    const body: string[] = [];
    const query: string[] = [];

    const takeNext = (index: number, flag: string): string | null => {
        const next = tokens[index + 1];

        if (undefined === next || next.startsWith('-')) {
            warnings.push(`Для ${flag} не указано значение.`);
            return null;
        }

        return next;
    };

    const addHeaderValue = (value: string): void => {
        if (value.includes(':')) {
            headers.push(value);
        } else {
            warnings.push(`Заголовок «${value}» пропущен: нужен формат «Name: value».`);
        }
    };

    const applyValue = (flag: string, value: string): void => {
        switch (flag) {
            case '--url':
                url = value;
                break;
            case '--url-query':
                query.push(encodeDataUrl(value));
                break;
            case '-X':
            case '--request':
                method = value.trim().toUpperCase();
                break;
            case '-H':
            case '--header':
                addHeaderValue(value);
                break;
            case '-d':
            case '--data':
            case '--data-ascii':
                if (value.startsWith('@')) {
                    warnings.push('Файлы из --data @file не поддерживаются.');
                } else {
                    body.push(value);
                }
                break;
            case '--data-raw':
                body.push(value);
                break;
            case '--data-urlencode':
                if (value.startsWith('@')) {
                    warnings.push('Файлы из --data-urlencode @file не поддерживаются.');
                } else {
                    body.push(encodeDataUrl(value));
                }
                break;
            case '--data-binary':
                if (value.startsWith('@')) {
                    warnings.push('Файлы из --data-binary @file не поддерживаются.');
                } else {
                    body.push(value);
                }
                break;
            case '--json':
                body.push(value);
                addHeader(headers, 'Content-Type', 'application/json');
                addHeader(headers, 'Accept', 'application/json');
                break;
            case '-F':
            case '--form':
            case '--form-string':
                warnings.push('multipart/form-data из -F не поддерживается: добавьте поля и файлы вручную.');
                break;
            case '-T':
            case '--upload-file':
                warnings.push('Загрузка файла через -T/--upload-file не поддерживается.');
                break;
            case '-u':
            case '--user':
                headers.push(`Authorization: Basic ${basicAuth(value)}`);
                break;
            case '-A':
            case '--user-agent':
                addHeader(headers, 'User-Agent', value);
                break;
            case '-e':
            case '--referer':
                addHeader(headers, 'Referer', value);
                break;
            case '-b':
            case '--cookie':
                if (value.startsWith('@')) {
                    warnings.push('Cookie-файл из -b @file не поддерживается: передайте заголовок Cookie.');
                } else {
                    addHeader(headers, 'Cookie', value);
                }
                break;
            case '-m':
            case '--max-time':
            case '--connect-timeout':
                timeout = clamp(Number.parseInt(value, 10), 1, 30);
                break;
            case '-o':
            case '--output':
            case '-w':
            case '--write-out':
            case '-c':
            case '--cookie-jar':
                warnings.push(`Флаг ${flag} влияет только на вывод cURL и не переносится.`);
                break;
            default:
                warnings.push(`Флаг ${flag} не поддерживается.`);
        }
    };

    const applyBoolean = (flag: string): void => {
        switch (flag) {
            case '-k':
            case '--insecure':
                verifyTls = false;
                break;
            case '-L':
            case '--location':
            case '--location-trusted':
                warnings.push('Редиректы (-L) не переносятся в настройки HTTP-инструмента.');
                break;
            case '-G':
            case '--get':
                get = true;
                break;
            case '-I':
            case '--head':
                method = 'HEAD';
                break;
            case '-O':
            case '--remote-name':
                warnings.push('Сохранение ответа в файл (-O) не переносится.');
                break;
            case '-4':
            case '--ipv4':
            case '-6':
            case '--ipv6':
                warnings.push(`Выбор IP-версии (${flag}) не переносится.`);
                break;
            case '--http1.0':
                warnings.push('HTTP/1.0 не поддерживается инструментом.');
                break;
            case '--http1.1':
                httpVersion = '1.1';
                break;
            case '--http2':
            case '--http2-prior-knowledge':
                httpVersion = '2';
                break;
            case '--http3':
                httpVersion = '3';
                break;
            default:
                // Остальные флаги влияют на вывод или отладку и на запрос не влияют.
                break;
        }
    };

    for (let index = 0; index < tokens.length; index++) {
        const raw = tokens[index];

        if ('--' === raw) {
            for (const rest of tokens.slice(index + 1)) {
                if ('' === url) {
                    url = rest;
                } else {
                    warnings.push(`Лишний аргумент «${rest}» пропущен.`);
                }
            }

            break;
        }

        if (raw.startsWith('--')) {
            const separator = raw.indexOf('=');
            const flag = separator > 0 ? raw.slice(0, separator) : raw;
            let value: string | null = separator > 0 ? raw.slice(separator + 1) : null;

            if (VALUE_FLAGS.has(flag)) {
                if (null === value) {
                    value = takeNext(index, flag);

                    if (null !== value) {
                        index++;
                    }
                }

                if (null !== value) {
                    applyValue(flag, value);
                }
            } else if (BOOLEAN_FLAGS.has(flag)) {
                if (null !== value) {
                    warnings.push(`Флаг ${flag} не принимает значение.`);
                }

                applyBoolean(flag);
            } else {
                warnings.push(`Флаг ${flag} не поддерживается.`);
            }

            continue;
        }

        if (raw.startsWith('-') && raw.length > 1) {
            let position = 1;

            while (position < raw.length) {
                const flag = `-${raw[position]}`;

                if (VALUE_FLAGS.has(flag)) {
                    let value = raw.slice(position + 1);

                    if ('' === value) {
                        const next = takeNext(index, flag);

                        if (null === next) {
                            break;
                        }

                        value = next;
                        index++;
                    }

                    applyValue(flag, value);
                    break;
                }

                if (BOOLEAN_FLAGS.has(flag)) {
                    applyBoolean(flag);
                    position++;
                    continue;
                }

                warnings.push(`Флаг ${flag} не поддерживается.`);
                position++;
            }

            continue;
        }

        if ('' === url) {
            url = raw;
        } else {
            warnings.push(`Лишний аргумент «${raw}» пропущен.`);
        }
    }

    if ('' === url) {
        return { request: null, warnings: unique(warnings), error: 'В команде cURL не найден URL.' };
    }

    let finalBody = body.join('&');
    const finalQuery = [...query];

    if (get) {
        if ('' !== finalBody) {
            finalQuery.push(finalBody);
        }

        finalBody = '';
    }

    if (finalQuery.length) {
        url += (url.includes('?') ? '&' : '?') + finalQuery.join('&');
    }

    return {
        request: {
            url,
            method: method || ('' === finalBody ? 'GET' : 'POST'),
            headers: headers.join('\n'),
            body: finalBody,
            base64Body: false,
            timeout,
            httpVersion,
            verifyTls,
        },
        warnings: unique(warnings),
        error: null,
    };
}

/** Генерирует cURL для текущих полей HTTP-инструмента. */
export function buildCurl(request: CurlRequest): { command: string; warnings: readonly string[] } {
    const warnings: string[] = [];
    const parts = ['curl', '--request', quote(request.method.trim().toUpperCase() || 'GET'), quote(request.url.trim())];

    for (const header of request.headers.split(/\r?\n/)) {
        const value = header.trim();

        if (value) {
            parts.push('--header', quote(value));
        }
    }

    if (request.body) {
        if (request.base64Body) {
            warnings.push('Тело в base64 нельзя точно экспортировать одной командой: декодируйте его или используйте --data-binary.');
        } else {
            parts.push('--data-raw', quote(request.body));
        }
    }

    if (!request.verifyTls) {
        parts.push('--insecure');
    }

    if (request.timeout !== 10) {
        parts.push('--max-time', String(request.timeout));
    }

    if ('1.1' === request.httpVersion) {
        parts.push('--http1.1');
    } else if ('2' === request.httpVersion) {
        parts.push('--http2');
    } else if ('3' === request.httpVersion) {
        parts.push('--http3');
    }

    return { command: parts.join(' '), warnings };
}

/**
 * Токенизация в духе POSIX shell: кавычки, экранирование,
 * перенос строки через `\` или `^` и ANSI-C строки `$'...'`.
 */
function tokenize(input: string): string[] {
    const tokens: string[] = [];
    let current = '';
    let hasCurrent = false;
    let quote: '"' | "'" | null = null;

    const push = (): void => {
        if (hasCurrent) {
            tokens.push(current);
            current = '';
            hasCurrent = false;
        }
    };

    for (let index = 0; index < input.length; index++) {
        const character = input[index];

        if (null !== quote) {
            if (character === quote) {
                quote = null;
                continue;
            }

            if ('\\' === character && '"' === quote) {
                const next = input[index + 1];

                if (undefined === next) {
                    current += character;
                    hasCurrent = true;
                    continue;
                }

                if ('\n' === next) {
                    index++;
                    continue;
                }

                if ('\r' === next && '\n' === input[index + 2]) {
                    index += 2;
                    continue;
                }

                if ('"\\$`'.includes(next)) {
                    current += next;
                    hasCurrent = true;
                    index++;
                    continue;
                }
            }

            current += character;
            hasCurrent = true;
            continue;
        }

        if (character === "'" || character === '"') {
            quote = character;
            hasCurrent = true;
            continue;
        }

        if ('$' === character && "'" === input[index + 1]) {
            index += 2;

            while (index < input.length && "'" !== input[index]) {
                if ('\\' !== input[index] || index + 1 >= input.length) {
                    current += input[index];
                    index++;
                    continue;
                }

                const escape = input[index + 1];

                switch (escape) {
                    case 'n':
                        current += '\n';
                        index += 2;
                        break;
                    case 't':
                        current += '\t';
                        index += 2;
                        break;
                    case 'r':
                        current += '\r';
                        index += 2;
                        break;
                    case 'a':
                        current += '\x07';
                        index += 2;
                        break;
                    case 'b':
                        current += '\b';
                        index += 2;
                        break;
                    case 'f':
                        current += '\f';
                        index += 2;
                        break;
                    case 'v':
                        current += '\v';
                        index += 2;
                        break;
                    case 'e':
                        current += '\x1B';
                        index += 2;
                        break;
                    case '0':
                        current += '\0';
                        index += 2;
                        break;
                    case 'x': {
                        const hex = input.slice(index + 2, index + 4);

                        if (/^[0-9a-f]{1,2}$/i.test(hex)) {
                            current += String.fromCharCode(Number.parseInt(hex, 16));
                            index += 2 + hex.length;
                        } else {
                            current += escape;
                            index += 2;
                        }

                        break;
                    }
                    case '\\':
                    case "'":
                    case '"':
                        current += escape;
                        index += 2;
                        break;
                    default:
                        current += '\\' + escape;
                        index += 2;
                }
            }

            if (index >= input.length) {
                throw new Error('Незакрытая кавычка в команде cURL.');
            }

            hasCurrent = true;
            continue;
        }

        if ('\\' === character) {
            const next = input[index + 1];

            if (undefined === next) {
                continue;
            }

            if ('\n' === next) {
                index++;
                continue;
            }

            if ('\r' === next && '\n' === input[index + 2]) {
                index += 2;
                continue;
            }

            current += next;
            hasCurrent = true;
            index++;
            continue;
        }

        if ('^' === character) {
            const next = input[index + 1];

            if ('\n' === next) {
                index++;
                continue;
            }

            if ('\r' === next && '\n' === input[index + 2]) {
                index += 2;
                continue;
            }
        }

        if (/\s/.test(character)) {
            push();
            continue;
        }

        current += character;
        hasCurrent = true;
    }

    if (null !== quote) {
        throw new Error('Незакрытая кавычка в команде cURL.');
    }

    push();

    return tokens;
}

function addHeader(headers: string[], name: string, value: string): void {
    const exists = headers.some((header) => header.toLowerCase().startsWith(`${name.toLowerCase()}:`));

    if (!exists) {
        headers.push(`${name}: ${value}`);
    }
}

function encodeDataUrl(value: string): string {
    const separator = value.indexOf('=');

    if (separator < 0) {
        return encodeURIComponent(value);
    }

    if (0 === separator) {
        return `=${encodeURIComponent(value.slice(1))}`;
    }

    return `${value.slice(0, separator)}=${encodeURIComponent(value.slice(separator + 1))}`;
}

function basicAuth(credentials: string): string {
    const bytes = new TextEncoder().encode(credentials);
    let binary = '';

    for (const byte of bytes) {
        binary += String.fromCharCode(byte);
    }

    return btoa(binary);
}

function quote(value: string): string {
    return `'${value.replace(/'/g, "'\\''")}'`;
}

function clamp(value: number, min: number, max: number): number {
    return Number.isFinite(value) ? Math.min(max, Math.max(min, value)) : min;
}

function unique(values: string[]): string[] {
    return [...new Set(values)];
}
