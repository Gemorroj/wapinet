import { TextToolError, convertText, escapeHtml } from './text-tools-codec';

describe('convertText', () => {
    it('encodes and decodes Base64 with Unicode', () => {
        const encoded = convertText('base64-encode', 'Привет');

        expect(convertText('base64-decode', encoded)).toBe('Привет');
        expect(convertText('base64-encode', 'hello')).toBe('aGVsbG8=');
    });

    it('encodes and decodes Base64 URL-safe', () => {
        const encoded = convertText('base64url-encode', 'a+b/c?');

        expect(encoded).not.toContain('+');
        expect(encoded).not.toContain('/');
        expect(encoded).not.toContain('=');
        expect(convertText('base64url-decode', encoded)).toBe('a+b/c?');
    });

    it('encodes and decodes URL and hex', () => {
        expect(convertText('url-decode', convertText('url-encode', 'привет мир?'))).toBe('привет мир?');
        expect(convertText('hex-encode', 'Hello')).toBe('48656c6c6f');
        expect(convertText('hex-decode', '0x48 65:6c 6c 6f')).toBe('Hello');
    });

    it('escapes and unescapes HTML', () => {
        const escaped = convertText('html-escape', `<a href="x">&'`);

        expect(escaped).toBe('&lt;a href=&quot;x&quot;&gt;&amp;&#39;');
        expect(convertText('html-unescape', escaped)).toBe(`<a href="x">&'`);
        expect(convertText('html-unescape', '&copy; &nbsp; &#x41;')).toBe('© \u00a0 A');
    });

    it('formats, minifies, escapes and unescapes JSON', () => {
        expect(convertText('json-minify', '{ "a": 1 }')).toBe('{"a":1}');
        expect(convertText('json-format', '{"a":1}')).toContain('\n  "a": 1');
        expect(convertText('json-escape', 'a"b')).toBe('"a\\"b"');
        expect(convertText('json-unescape', '"a\\"b"')).toBe('a"b');
    });

    it('converts between query strings and JSON', () => {
        const json = JSON.parse(convertText('query-to-json', '?a=1&b=2&a=3')) as Record<string, unknown>;

        expect(json).toEqual({ a: ['1', '3'], b: '2' });
        expect(convertText('json-to-query', '{"a":["1","2"],"b":"x y"}')).toBe('a=1&a=2&b=x+y');
    });

    it('parses URL components', () => {
        const parsed = JSON.parse(convertText('url-parse', 'https://example.com:8443/path?q=1#top')) as Record<string, string>;

        expect(parsed['hostname']).toBe('example.com');
        expect(parsed['port']).toBe('8443');
        expect(parsed['pathname']).toBe('/path');
        expect(parsed['search']).toBe('?q=1');
        expect(parsed['hash']).toBe('#top');
    });

    it('converts text case', () => {
        expect(convertText('title', 'привет world')).toBe('Привет World');
        expect(convertText('camel', 'hello world')).toBe('helloWorld');
        expect(convertText('pascal', 'hello world')).toBe('HelloWorld');
        expect(convertText('snake', 'helloWorld')).toBe('hello_world');
        expect(convertText('kebab', 'HTTP Server')).toBe('http-server');
        expect(convertText('constant', 'hello world')).toBe('HELLO_WORLD');
        expect(convertText('lower', 'ПРИВЕТ')).toBe('привет');
        expect(convertText('upper', 'привет')).toBe('ПРИВЕТ');
    });

    it('works with lines', () => {
        expect(convertText('lines-sort', 'b\na\nc')).toBe('a\nb\nc');
        expect(convertText('lines-unique', 'a\nb\na')).toBe('a\nb');
        expect(convertText('lines-trim', ' a \n b ')).toBe('a\nb');
        expect(convertText('lines-remove-empty', 'a\n\n b \n')).toBe('a\n b ');
        expect(convertText('lines-reverse', 'a\nb\nc')).toBe('c\nb\na');
    });

    it('reports invalid input', () => {
        expect(() => convertText('json-format', '{')).toThrow(TextToolError);
        expect(() => convertText('hex-decode', 'abc')).toThrow(TextToolError);
        expect(() => convertText('base64-decode', '@@@')).toThrow(TextToolError);
        expect(() => convertText('url-decode', '%')).toThrow(TextToolError);
        expect(() => convertText('url-parse', 'example.com')).toThrow(TextToolError);
        expect(() => convertText('json-to-query', '[]')).toThrow(TextToolError);
        expect(() => convertText('missing', '')).toThrow(TextToolError);
    });
});

describe('escapeHtml', () => {
    it('escapes the same characters as htmlspecialchars with ENT_QUOTES', () => {
        expect(escapeHtml(`&<>"'`)).toBe('&amp;&lt;&gt;&quot;&#39;');
        expect(escapeHtml('<a href="x">Текст & Co</a>')).toBe('&lt;a href=&quot;x&quot;&gt;Текст &amp; Co&lt;/a&gt;');
    });
});
