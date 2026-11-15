import assert from 'node:assert/strict';
import { createServer } from 'node:http';
import { test } from 'node:test';
import { fetchOpenApi } from './export-openapi.mjs';

async function serve(t, status, body) {
    const server = createServer((_request, response) => {
        response.writeHead(status, { 'Content-Type': 'application/json' });
        response.end(JSON.stringify(body));
    });
    await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
    t.after(() => new Promise((resolve, reject) => server.close((error) => (error ? reject(error) : resolve()))));

    return `http://127.0.0.1:${server.address().port}/api/doc.json`;
}

test('downloads a valid OpenAPI document', async (t) => {
    const schema = { openapi: '3.1.0', paths: {} };
    const url = await serve(t, 200, schema);

    assert.deepEqual(await fetchOpenApi(url), schema);
});

test('rejects an unsuccessful response', async (t) => {
    const url = await serve(t, 503, { error: 'unavailable' });

    await assert.rejects(fetchOpenApi(url), /HTTP 503/);
});

test('rejects JSON which is not an OpenAPI document', async (t) => {
    const url = await serve(t, 200, { error: 'not a schema' });

    await assert.rejects(fetchOpenApi(url), /invalid OpenAPI document/);
});
