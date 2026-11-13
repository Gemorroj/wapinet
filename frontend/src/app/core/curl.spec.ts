import { buildCurl, CurlRequest, parseCurl } from './curl';

function request(overrides: Partial<CurlRequest> = {}): CurlRequest {
    return {
        url: 'https://api.example.com/',
        method: 'GET',
        headers: '',
        body: '',
        base64Body: false,
        timeout: 10,
        httpVersion: 'auto',
        verifyTls: true,
        ...overrides,
    };
}

describe('parseCurl', () => {
    it('parses a Chrome DevTools command with line continuations', () => {
        const result = parseCurl(`curl 'https://api.example.com/users' \\
  -H 'accept: application/json' \\
  -H 'content-type: application/json' \\
  --data-raw '{"name":"Ivan"}' \\
  --compressed`);

        expect(result.error).toBeNull();
        expect(result.warnings).toEqual([]);
        expect(result.request).toEqual(
            request({
                url: 'https://api.example.com/users',
                method: 'POST',
                headers: 'accept: application/json\ncontent-type: application/json',
                body: '{"name":"Ivan"}',
            }),
        );
    });

    it('parses method, auth, cookies, timeout and TLS flags', () => {
        const result = parseCurl(`curl --request PUT 'https://api.example.com/items/1' -u 'user:p@ss' -A 'Test Agent' -b 'session=1' -k --max-time 45 --http2`);

        expect(result.error).toBeNull();
        expect(result.request?.method).toBe('PUT');
        expect(result.request?.headers.split('\n')).toEqual([`Authorization: Basic ${btoa('user:p@ss')}`, 'User-Agent: Test Agent', 'Cookie: session=1']);
        expect(result.request?.timeout).toBe(30);
        expect(result.request?.httpVersion).toBe('2');
        expect(result.request?.verifyTls).toBe(false);
    });

    it('moves -G data into the query string', () => {
        const result = parseCurl(`curl -G 'https://example.com/search' --data-urlencode 'q=hello world' -d 'page=1'`);

        expect(result.request?.url).toBe('https://example.com/search?q=hello%20world&page=1');
        expect(result.request?.method).toBe('GET');
        expect(result.request?.body).toBe('');
    });

    it('supports clustered short flags and attached values', () => {
        const result = parseCurl(`curl -sSk -XPOST 'https://example.com' -H'X-Test: 1' -d'a=1'`);

        expect(result.error).toBeNull();
        expect(result.request?.method).toBe('POST');
        expect(result.request?.verifyTls).toBe(false);
        expect(result.request?.headers).toBe('X-Test: 1');
        expect(result.request?.body).toBe('a=1');
    });

    it('warns about unsupported forms, files and proxies', () => {
        const result = parseCurl(`curl https://example.com -F 'file=@a.txt' -d @payload.json --proxy http://proxy:8080`);

        expect(result.request?.url).toBe('https://example.com');
        expect(result.warnings).toEqual(
            expect.arrayContaining([
                expect.stringContaining('multipart/form-data'),
                expect.stringContaining('--data @file'),
                expect.stringContaining('--proxy'),
            ]),
        );
    });

    it('ignores a leading curl executable name', () => {
        expect(parseCurl(`curl.exe 'https://example.com/'`).request?.url).toBe('https://example.com/');
    });

    it('reports an unterminated quote', () => {
        const result = parseCurl(`curl 'https://example.com/`);

        expect(result.request).toBeNull();
        expect(result.error).toContain('кавычка');
    });

    it('reports a missing URL', () => {
        const result = parseCurl(`curl -k --compressed`);

        expect(result.request).toBeNull();
        expect(result.error).toContain('URL');
    });

    it('warns about unknown flags', () => {
        const result = parseCurl(`curl 'https://example.com/' --definitely-unknown`);

        expect(result.warnings).toEqual([expect.stringContaining('--definitely-unknown')]);
    });
});

describe('buildCurl', () => {
    it('builds a command that round-trips through parseCurl', () => {
        const source = request({
            url: 'https://api.example.com/items?page=2',
            method: 'PATCH',
            headers: "Accept: application/json\nX-Note: it's fine",
            body: '{"active":true}',
            timeout: 20,
            httpVersion: '2',
            verifyTls: false,
        });
        const built = buildCurl(source);
        const parsed = parseCurl(built.command);

        expect(built.warnings).toEqual([]);
        expect(parsed.error).toBeNull();
        expect(parsed.request?.url).toBe(source.url);
        expect(parsed.request?.method).toBe(source.method);
        expect(parsed.request?.headers).toBe(source.headers);
        expect(parsed.request?.body).toBe(source.body);
        expect(parsed.request?.timeout).toBe(source.timeout);
        expect(parsed.request?.httpVersion).toBe(source.httpVersion);
        expect(parsed.request?.verifyTls).toBe(false);
    });

    it('escapes single quotes', () => {
        expect(buildCurl(request({ body: `it's` })).command).toContain(`'it'\\''s'`);
    });

    it('warns that a base64 body cannot be exported as one command', () => {
        const built = buildCurl(request({ body: 'SGVsbG8=', base64Body: true }));

        expect(built.command).not.toContain('SGVsbG8=');
        expect(built.warnings[0]).toContain('base64');
    });
});
