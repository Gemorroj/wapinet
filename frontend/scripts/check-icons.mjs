import { existsSync, readdirSync, readFileSync } from 'node:fs';
import { join, relative } from 'node:path';

const appDir = new URL('../src/app/', import.meta.url).pathname.replace(/^\/([A-Za-z]:)/, '$1');
const iconsDir = new URL('../../public/resources/taiga-ui/icons/', import.meta.url).pathname.replace(/^\/([A-Za-z]:)/, '$1');

function walk(directory) {
    return readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
        const path = join(directory, entry.name);

        if (entry.isDirectory()) {
            return 'api' === entry.name ? [] : walk(path);
        }

        return entry.name.endsWith('.html') || entry.name.endsWith('.ts') ? [path] : [];
    });
}

const icons = new Set();

for (const file of walk(appDir)) {
    const content = readFileSync(file, 'utf8');

    for (const match of content.matchAll(/@tui\.([a-z0-9-]+)/g)) {
        icons.add(match[1]);
    }
}

const missing = [...icons].filter((icon) => !existsSync(join(iconsDir, `${icon}.svg`))).sort();

if (missing.length > 0) {
    console.error(`Missing Taiga UI icons in public/resources/taiga-ui/icons: ${missing.join(', ')}`);
    process.exit(1);
}

console.log(`Taiga UI icons: ${icons.size} used, all present.`);
