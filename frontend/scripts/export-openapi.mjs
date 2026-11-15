import { rename, rm, writeFile } from 'node:fs/promises';
import process from 'node:process';

export async function fetchOpenApi(url) {
    const response = await fetch(url, { signal: AbortSignal.timeout(30_000) });

    if (!response.ok) {
        throw new Error(`OpenAPI export failed: HTTP ${response.status}`);
    }

    const schema = await response.json();

    if (typeof schema?.openapi !== 'string' || !schema.paths || typeof schema.paths !== 'object') {
        throw new Error('The backend returned an invalid OpenAPI document');
    }

    return schema;
}

if (import.meta.main) {
    const source = new URL('/api/doc.json', process.env.API_TARGET || 'http://localhost');
    const schema = await fetchOpenApi(source);
    const destination = new URL('../openapi.json', import.meta.url);
    const temporary = new URL(`../openapi.json.${process.pid}.tmp`, import.meta.url);

    try {
        await writeFile(temporary, `${JSON.stringify(schema, null, 4)}\n`);
        await rename(temporary, destination);
    } finally {
        await rm(temporary, { force: true });
    }
}
