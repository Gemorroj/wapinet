import { expect, test } from '@playwright/test';

test('home page renders', async ({ page }) => {
    await page.goto('/');

    await expect(page).toHaveTitle(/Wapinet/);
    await expect(page.getByRole('link', { name: 'Файлообменник' }).first()).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Текст' })).toBeVisible();
    await expect(page.getByRole('link', { name: /Инструменты текста/ })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Код' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Сеть' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'WHOIS / RDAP' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'CSS-валидатор' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Хэши' })).toBeVisible();
    await expect(page.locator('main').getByRole('link', { name: 'Новости' })).toHaveCount(0);
    await expect(page.locator('header').getByRole('link', { name: 'Новости' })).toBeVisible();
});

test('file list loads and opens a file', async ({ page }) => {
    await page.goto('/files');

    const firstRow = page.locator('.app-list > [tuiCell]').first();

    await expect(firstRow).toBeVisible();
    await firstRow.locator('a').first().click();
    await expect(page.locator('.file-detail__name')).toBeVisible();
});

test('unknown route shows the Angular not found page', async ({ page }) => {
    await page.goto('/this-route-does-not-exist');

    await expect(page.getByRole('heading', { name: 'Страница не найдена' })).toBeVisible();
});

test('login page renders the form', async ({ page }) => {
    await page.goto('/login');

    await expect(page.getByRole('heading', { name: 'Вход' })).toBeVisible();
    await expect(page.getByLabel('Логин')).toBeVisible();
});

test('whois page renders a structured lookup', async ({ page }) => {
    const expiry = new Date(Date.now() + 30 * 86_400_000).toISOString();

    await page.route('**/api/whois', (route) =>
        route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
                query: 'example.com',
                kind: 'domain',
                protocol: 'rdap',
                server: 'https://rdap.example.test/',
                raw: '{"ldhName":"example.com"}',
                geo: null,
                details: [{ label: 'Домен', value: 'example.com' }],
                statuses: ['client delete prohibited'],
                events: [{ action: 'Истекает', date: expiry }],
                nameservers: ['ns1.example.com'],
                contacts: [{ role: 'registrar', name: 'Example Registrar', organization: null, email: 'registrar@example.com' }],
                dns: {
                    records: [{ type: 'NS', value: 'ns1.example.com' }],
                    ips: [{ ip: '8.8.8.8', hostname: 'dns.google', geo: { country: 'США', countryCode: 'US' } }],
                    hosts: [],
                },
            }),
        }),
    );

    await page.goto('/whois?query=example.com');

    await expect(page.getByRole('heading', { name: 'Результат' })).toBeVisible();
    await expect(page.getByText('Example Registrar')).toBeVisible();
    await expect(page.locator('.whois app-code-block .hljs-attr')).toBeVisible();
    await expect(page.locator('.whois__expiry')).toContainText('Осталось 30 дней');
    await expect(page.locator('.whois__status')).toHaveClass(/whois__status_ok/);
    await expect(page.locator('a[href*="query=ns1.example.com"]').first()).toBeVisible();
    await expect(page.locator('a[href*="query=8.8.8.8"]').first()).toBeVisible();
});

test('http client imports a curl command', async ({ page }) => {
    await page.goto('/http');

    await page.getByRole('button', { name: 'Импорт' }).click();
    await page
        .locator('textarea[name="curlImport"]')
        .fill(`curl 'https://api.example.com/users' -H 'Accept: application/json' --data-raw '{"a":1}' --max-time 15`);
    await page.getByRole('button', { name: 'Заполнить запрос' }).click();

    await expect(page.locator('input[name="url"]')).toHaveValue('https://api.example.com/users');
    await expect(page.locator('input[name="methodSelect"]')).toHaveValue('POST');
    await expect(page.locator('textarea[name="body"]')).toHaveValue('{"a":1}');
    await expect(page.locator('textarea[name="headers"]')).toHaveValue('Accept: application/json');
});

test('archiver file source has a drop zone', async ({ page }) => {
    await page.goto('/archiver/create');

    await expect(page.locator('.file-source__drop')).toBeVisible();
});

test('archiver page renders the mode chooser', async ({ page }) => {
    await page.goto('/archiver');

    await expect(page.getByRole('link', { name: 'Создать архив' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Распаковать архив' })).toBeVisible();
});

test('archiver opens a saved archive link', async ({ page }) => {
    const archive = 'archive'.concat('a'.repeat(16));
    const response = {
        archive,
        items: [{ path: 'hello.txt', size: 5, isDirectory: false, isEncrypted: false }],
        size: 5,
        format: 'zip',
        sourceSize: 2048,
    };

    await page.route('**/api/archiver**', async (route) => {
        const path = new URL(route.request().url()).pathname;

        if ('POST' === route.request().method() && path.endsWith('/api/archiver/create')) {
            return route.fulfill({ json: response });
        }

        if ('GET' === route.request().method() && path.endsWith(`/api/archiver/${archive}`)) {
            return route.fulfill({ json: response });
        }

        return route.fulfill({ status: 204, body: '' });
    });

    await page.goto('/archiver/create');
    await page.locator('input[type="file"]').setInputFiles({
        name: 'hello.txt',
        mimeType: 'text/plain',
        buffer: Buffer.from('hello'),
    });
    await page.getByRole('button', { name: 'Создать', exact: true }).click();

    await expect(page).toHaveURL(new RegExp(`/archiver/edit/${archive}$`));
    await expect(page.locator('.archiver')).toContainText('hello.txt');

    await page.reload();
    await expect(page.locator('.archiver')).toContainText('hello.txt');
});

test('unicode converter encodes text in the browser', async ({ page }) => {
    await page.goto('/unicode');

    await page.locator('textarea[name="text"]').fill('Привет');

    await expect(page.locator('.unicode app-code-block')).toContainText('&#x041f;&#x0440;&#x0438;&#x0432;&#x0435;&#x0442;');
});

test('unicode tabs switch by url', async ({ page }) => {
    await page.goto('/unicode');

    await page.getByRole('button', { name: 'Символы', exact: true }).click();

    await expect(page).toHaveURL(/\/unicode\/icons$/);
    await expect(page.getByRole('heading', { name: 'Справочник символов' })).toBeVisible();
    await expect(page.locator('app-breadcrumbs')).toContainText('Unicode');
    await expect(page.locator('app-breadcrumbs a[href="/unicode"]')).toBeVisible();
});

test('unicode converter decodes entities', async ({ page }) => {
    await page.goto('/unicode');

    await page.getByRole('button', { name: 'Декодировать' }).click();
    await page.locator('textarea[name="text"]').fill('&#x041f;&#x0440;&#x0438;');

    await expect(page.locator('.unicode app-code-block')).toContainText('При');
});

test('unicode inspector shows character details', async ({ page }) => {
    await page.goto('/unicode/inspector');

    await page.locator('textarea[name="inspect"]').fill('😀');

    await expect(page.locator('.unicode__table')).toContainText('U+1F600');
    await expect(page.locator('.unicode__table')).toContainText('F0 9F 98 80');
});

test('unicode about tab renders the overview', async ({ page }) => {
    await page.goto('/unicode/about');

    await expect(page.getByRole('heading', { name: 'Что такое Unicode' })).toBeVisible();
    await expect(page.getByText('UTF-8', { exact: true }).first()).toBeVisible();
});

test('unicode icons tab searches the reference', async ({ page }) => {
    await page.goto('/unicode/icons');

    await page.locator('input[name="query"]').fill('тире');

    await expect(page.locator('.unicode__table')).toContainText('тире длины N');
});

test('php obfuscator sends code to the api and has a help tab', async ({ page }) => {
    await page.route('**/api/php-obfuscator', (route) => {
        if ('GET' === route.request().method()) {
            return route.fulfill({ json: { phpVersion: '8.5.12', maxCodeSize: 262144 } });
        }

        return route.fulfill({ json: { code: "<?php echo base64_decode('SGVsbG8=');" } });
    });

    await page.goto('/php_obfuscator');

    await page.locator('textarea[name="code"]').fill('<?php echo "Hello";');
    await page.getByRole('button', { name: 'Обфусцировать' }).click();

    await expect(page.locator('.php-obfuscator')).toContainText('base64_decode');

    await page.getByRole('button', { name: 'Справка', exact: true }).click();

    await expect(page).toHaveURL(/\/php_obfuscator\/about$/);
    await expect(page.locator('.php-obfuscator')).toContainText('Чего она не делает');
    await expect(page.locator('.php-obfuscator')).toContainText('PHP до 8.5');
    await expect(page.locator('app-breadcrumbs')).toContainText('Об обфускации');
});

test('php obfuscator accepts a link as the source', async ({ page }) => {
    let body: Record<string, unknown> = {};

    await page.route('**/api/php-obfuscator', (route) => {
        if ('GET' === route.request().method()) {
            return route.fulfill({ json: { phpVersion: '8.5.12', maxCodeSize: 262144 } });
        }

        body = route.request().postDataJSON();

        return route.fulfill({ json: { code: "<?php echo base64_decode('SGVsbG8=');" } });
    });

    await page.goto('/php_obfuscator');

    await page.getByRole('button', { name: 'Файл или ссылка' }).click();
    await page.getByRole('button', { name: 'Ссылка', exact: true }).click();
    await page.getByLabel('Ссылка на файл').fill('https://example.com/a.php');
    await page.getByRole('button', { name: 'Обфусцировать' }).click();

    await expect(page.locator('.php-obfuscator')).toContainText('base64_decode');
    expect(body['url']).toBe('https://example.com/a.php');
});

test('php validator reports errors and has a help tab', async ({ page }) => {
    await page.route('**/api/php-validator', (route) =>
        route.fulfill({
            json: {
                valid: false,
                messages: [{ severity: 'error', type: 'Parse error', line: 1, column: null, message: 'Unclosed "("', extract: null }],
            },
        }),
    );

    await page.goto('/php_validator');

    await page.locator('textarea[name="code"]').fill('<?php if (');
    await page.getByRole('button', { name: 'Проверить' }).click();

    await expect(page.locator('.validator')).toContainText('Найдены ошибки: 1.');
    await expect(page.locator('.validator')).toContainText('Unclosed "("');
    await expect(page.locator('.validator')).toContainText('Строка 1');

    await page.getByRole('button', { name: 'Справка', exact: true }).click();

    await expect(page).toHaveURL(/\/php_validator\/about$/);
    await expect(page.locator('.validator')).toContainText('синтаксис PHP');
    await expect(page.locator('app-breadcrumbs')).toContainText('Справка');
});

test('validator accepts a link as the source', async ({ page }) => {
    let body: Record<string, unknown> = {};

    await page.route('**/api/php-validator', async (route) => {
        body = route.request().postDataJSON();
        await route.fulfill({ json: { valid: true, messages: [] } });
    });

    await page.goto('/php_validator');

    await page.getByRole('button', { name: 'Файл или ссылка' }).click();
    await page.getByRole('button', { name: 'Ссылка', exact: true }).click();
    await page.getByLabel('Ссылка на файл').fill('https://example.com/a.php');
    await page.getByRole('button', { name: 'Проверить' }).click();

    await expect(page.locator('.validator')).toContainText('Ошибок не найдено.');
    expect(body['url']).toBe('https://example.com/a.php');
});

test('audio tags opens a file and saves tags', async ({ page }) => {
    const id = 'a'.repeat(32);
    const info = {
        id,
        originalName: 'sample.mp3',
        size: 1024,
        format: 'mp3',
        bitrate: 128000,
        bitrateMode: 'cbr',
        sampleRate: 44100,
        channelMode: 'stereo',
        tagFormats: ['id3v1', 'id3v2.4'],
        canWrite: true,
        otherTags: [],
        tags: {
            title: '',
            albumArtist: '',
            artist: '',
            album: '',
            year: '',
            trackNumber: '',
            urlUser: '',
            genre: '',
            comment: '',
            hasPicture: false,
        },
    };

    await page.route('**/api/audio-tags**', async (route) => {
        const path = new URL(route.request().url()).pathname;

        if ('POST' === route.request().method() && path.endsWith('/api/audio-tags')) {
            return route.fulfill({ status: 201, json: info });
        }

        if ('POST' === route.request().method()) {
            return route.fulfill({ json: { ...info, tags: { ...info.tags, title: 'New title' } } });
        }

        if ('GET' === route.request().method() && path.endsWith(`/api/audio-tags/${id}`)) {
            return route.fulfill({ json: info });
        }

        return route.fulfill({ status: 204, body: '' });
    });

    await page.goto('/audio_tags');
    await page.locator('input[type="file"]').setInputFiles({
        name: 'sample.mp3',
        mimeType: 'audio/mpeg',
        buffer: Buffer.from('audio'),
    });
    await page.getByRole('button', { name: 'Открыть' }).click();

    await expect(page).toHaveURL(new RegExp(`/audio_tags/${id}$`));
    await expect(page.locator('.audio-tags')).toContainText('sample.mp3');
    await expect(page.locator('.audio-tags')).toContainText('id3v2.4');

    await page.reload();
    await expect(page.locator('.audio-tags')).toContainText('sample.mp3');

    await page.locator('input[name="title"]').fill('Draft title');
    await page.getByRole('button', { name: 'Справка', exact: true }).click();
    await expect(page).toHaveURL(/\/audio_tags\/about$/);
    await page.getByRole('button', { name: 'Редактор', exact: true }).click();
    await expect(page).toHaveURL(new RegExp(`/audio_tags/${id}$`));
    await expect(page.locator('input[name="title"]')).toHaveValue('Draft title');

    await page.locator('input[name="title"]').fill('New title');
    await page.getByRole('button', { name: 'Сохранить' }).click();

    await expect(page.locator('.audio-tags')).toContainText('Теги сохранены.');
});

test('css validator sends the selected profile', async ({ page }) => {
    let body: Record<string, unknown> = {};

    await page.route('**/api/css-validator', async (route) => {
        body = route.request().postDataJSON();
        await route.fulfill({ json: { valid: true, messages: [] } });
    });

    await page.goto('/css_validator');

    await page.locator('select[name="profile"]').selectOption({ label: 'CSS 2.1' });
    await page.locator('select[name="warnings"]').selectOption({ label: 'Все' });
    await page.locator('textarea[name="code"]').fill('body { color: red; }');
    await page.getByRole('button', { name: 'Проверить' }).click();

    await expect(page.locator('.validator')).toContainText('Ошибок не найдено.');
    expect(body['profile']).toBe('css21');
    expect(body['warnings']).toBe('2');
});

test('text tools encode Base64', async ({ page }) => {
    await page.goto('/text-tools');

    await page.locator('textarea[name="text"]').fill('hello');

    await expect(page.locator('.text-tools app-code-block')).toContainText('aGVsbG8=');
});

test('text tools tabs update url and breadcrumbs', async ({ page }) => {
    await page.goto('/text-tools');

    await page.getByRole('button', { name: 'Текст' }).click();

    await expect(page).toHaveURL(/\/text-tools\/text$/);
    await expect(page.locator('app-breadcrumbs')).toContainText('Инструменты текста');
    await expect(page.locator('app-breadcrumbs')).toContainText('Текст');
});

test('legacy code url redirects to text tools', async ({ page }) => {
    await page.goto('/code/data');

    await expect(page).toHaveURL(/\/text-tools\/data$/);
});

test('hash tool hashes text and a remote file', async ({ page }) => {
    let requestBody: unknown = null;

    await page.route('**/api/hash', (route) => {
        requestBody = route.request().postDataJSON();

        return route.fulfill({
            json: {
                hashes: {
                    md5: '900150983cd24fb0d6963f7d28e17f72',
                    sha1: 'a9993e364706816aba3e25717850c26c9cd0d89d',
                    sha256: 'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad',
                    sha512: 'sha512-value',
                    crc32: '352441c2',
                },
            },
        });
    });

    await page.goto('/hash');

    await expect(page.getByRole('heading', { name: 'Хэши', level: 2 })).toBeVisible();
    await expect(page.locator('app-breadcrumbs')).toContainText('Хэши');

    await expect(page.getByText('Для проверки файлов берите SHA-256.')).toBeVisible();

    await page.locator('textarea[name="text"]').fill('hello');

    await expect(page.locator('.hash')).toContainText('5d41402abc4b2a76b9719d911017c592');
    await expect(page.locator('.hash')).toContainText('9b71d224bd62f3785d96d46ad3ea3d73319bfbc2890caadae2dff72519673ca7');

    await page.getByRole('button', { name: 'SHA3-256', exact: true }).click();

    await expect(page.locator('.hash')).toContainText('3338be694f50c5f338814986cdf0686453a888b84f424d792af4b9202398f392');

    await page.getByRole('button', { name: 'Файл' }).click();
    await page.getByRole('button', { name: 'Ссылка' }).click();
    await page.locator('input[type="url"]').fill('https://example.com/abc.txt');

    await page.getByRole('button', { name: 'Посчитать хэш' }).click();

    await expect(page.locator('.hash')).toContainText('900150983cd24fb0d6963f7d28e17f72');

    expect(requestBody).toMatchObject({
        url: 'https://example.com/abc.txt',
        algorithms: ['md5', 'sha256', 'sha512', 'sha3-256'],
    });
});

test('tool tabs keep the current state', async ({ page }) => {
    await page.goto('/hash');
    await page.locator('textarea[name="text"]').fill('keep me');

    await page.getByRole('button', { name: 'Справка', exact: true }).click();
    await expect(page).toHaveURL(/\/hash\/about$/);

    await page.getByRole('button', { name: 'Хэши', exact: true }).click();
    await expect(page).toHaveURL(/\/hash$/);
    await expect(page.locator('textarea[name="text"]')).toHaveValue('keep me');
});

test('hash about tab explains algorithms', async ({ page }) => {
    await page.goto('/hash');

    await page.getByRole('button', { name: 'Справка', exact: true }).click();

    await expect(page).toHaveURL(/\/hash\/about$/);
    await expect(page.getByRole('heading', { name: 'Что такое хэш' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Какой алгоритм выбрать' })).toBeVisible();
    await expect(page.getByText('CRC32, CRC32C')).toBeVisible();
    await expect(page.locator('app-breadcrumbs')).toContainText('О хэшах');
});

test('file statistics page renders the totals', async ({ page }) => {
    await page.route('**/api/files/statistics', (route) =>
        route.fulfill({
            json: {
                categories: { video: 15 },
                countFiles: 15,
                countViews: 120,
                sizeFiles: 2048,
                users: [{ username: 'alice', uploads: 4 }],
            },
        }),
    );

    await page.goto('/files/statistic');

    await expect(page.locator('.statistic')).toContainText('Файлов');
    await expect(page.locator('.statistic')).toContainText('2.0 КБ');
    await expect(page.locator('.statistic')).toContainText('alice');
    await expect(page.locator('app-breadcrumbs')).toContainText('Статистика');
});

test('file categories open the filtered file list', async ({ page }) => {
    await page.route('**/api/files/statistics', (route) =>
        route.fulfill({ json: { categories: { video: 12 }, countFiles: 12, countViews: 3, sizeFiles: 1024, users: [] } }),
    );

    await page.goto('/files/categories');

    await expect(page.locator('.categories')).toContainText('Видео');
    await expect(page.locator('.categories')).toContainText('12');

    await page.locator('.categories a', { hasText: 'Видео' }).click();

    await expect(page).toHaveURL(/\/files\?category=video/);
});

test('file information page renders the rules', async ({ page }) => {
    await page.goto('/files/information');

    await expect(page.getByRole('heading', { name: 'Запрещается' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Хранение файлов' })).toBeVisible();
    await expect(page.locator('.information a[href="mailto:admin@wapinet.ru"]')).toBeVisible();
});

test('news list opens an article', async ({ page }) => {
    const createdAt = new Date().toISOString();

    await page.route('**/api/news**', (route) => {
        const path = new URL(route.request().url()).pathname;

        if (path.endsWith('/api/news/5')) {
            return route.fulfill({
                json: { id: 5, subject: 'Запуск сайта', body: 'Текст', bodyHtml: '<p>Полный текст новости</p>', createdAt, createdBy: null, updatedAt: null },
            });
        }

        return route.fulfill({ json: { items: [{ id: 5, subject: 'Запуск сайта', createdAt, updatedAt: null }], page: 1, perPage: 20, total: 1 } });
    });

    await page.goto('/news');

    await expect(page.locator('.news')).toContainText('Запуск сайта');

    await page.getByRole('link', { name: 'Запуск сайта' }).click();

    await expect(page).toHaveURL(/\/news\/5$/);
    await expect(page.locator('.news-detail')).toContainText('Полный текст новости');
});

test('gists list renders and searches', async ({ page }) => {
    const createdAt = new Date().toISOString();

    await page.route('**/api/gists**', (route) =>
        route.fulfill({
            json: {
                items: [
                    {
                        id: 7,
                        subject: 'Мой блог',
                        avatarUrl: '',
                        canDelete: false,
                        canEdit: false,
                        createdAt,
                        isOnline: false,
                        updatedAt: null,
                        user: { username: 'bob' },
                    },
                ],
                page: 1,
                perPage: 20,
                total: 1,
            },
        }),
    );

    await page.goto('/gist');

    await expect(page.locator('.gists')).toContainText('Мой блог');
    await expect(page.locator('app-breadcrumbs')).toContainText('Блоги');

    await page.getByLabel('Поиск по блогам').fill('php');
    await page.getByLabel('Поиск по блогам').press('Enter');

    await expect(page).toHaveURL(/[?&]q=php/);
});

test('protected pages redirect to login', async ({ page }) => {
    await page.goto('/user/users');

    await expect(page).toHaveURL(/\/login\?returnUrl=/);
    await expect(page.getByRole('heading', { name: 'Вход' })).toBeVisible();
});
