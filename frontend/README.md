# Frontend wapinet.ru

SPA на Angular 22 и Taiga UI 5. Работает с Symfony REST API по `/api`: страницы открываются с корня сайта, файлы сборки лежат в `/app/`.

## Быстрый старт

Для Docker команды выполняются из корня репозитория: `docker compose up -d --build`, затем `docker compose exec frontend npm run build` или `docker compose exec frontend npm run api:sync`. Dev-сервер уже запущен на `http://localhost:4200`. Подробности и инициализация БД — в [основном README](../README.md#локальная-разработка-в-docker).

Без Docker:

```bash
npm ci
npm start        # http://localhost:4200
```

Запросы `/api`, `/resources`, `/static` и `/media` проксируются на бэкенд. По умолчанию это `http://localhost`, другой адрес — через `API_TARGET`:

```powershell
$env:API_TARGET = 'http://your-local-host'
npm start
```

```bash
npm run build    # production-сборка в ../public/app; коммитится, на проде Node.js не нужен
```

## Структура

- `src/app/core` — инфраструктура: авторизация, HTTP, клиенты API, общие утилиты;
- `src/app/shared` — переиспользуемые UI-компоненты;
- `src/app/pages` — страницы; папка соответствует первому сегменту URL;
- `src/app/api/generated` — сгенерированный OpenAPI-клиент, руками не править.

Иконки Taiga UI лежат в `../public/resources/taiga-ui/icons` и раздаются Symfony, а не собираются в SPA. Новую иконку нужно скопировать из `node_modules/@taiga-ui/icons/src/<name>.svg` в этот каталог (флаги — в подкаталог `flags/`); полноту набора проверяет `npm run icons:check`.

## API-клиент

Типизированный клиент генерируется из OpenAPI-схемы. Запускать после изменения API на бэкенде:

```bash
npm run api:sync   # получить /api/doc.json и перегенерировать клиент
npm run api:doc    # только получить схему
npm run api:gen    # перегенерировать по существующей схеме
```

`openapi.json` хранится в репозитории, поэтому клиент можно обновить без запущенного backend.

`api:doc` и `api:sync` требуют работающий backend: адрес берётся из `API_TARGET` (по умолчанию `http://localhost`, в Docker — `http://angie`). Ошибка загрузки схемы сохраняет прежний `openapi.json`. Для генерации по уже сохранённой схеме достаточно `api:gen`.

Загрузка файлов с тегами собирается вручную в `src/app/core/api/files-api.ts`: PHP разбирает повторяющиеся поля в массив только при нотации `tags[]`, которую сгенерированный клиент не создаёт.

Источник файла выбирается общим компонентом `src/app/shared/file-source`: локальные файлы, drag-and-drop или ссылка. Сервер скачивает файл по ссылке сам.

## Авторизация

Вход — через `POST /api/auth/login`, далее используется opaque Bearer-токен. Токен хранится локально, подставляется интерцептором и очищается при `401`. Защищённые маршруты — `authGuard` и `adminGuard`.

## Проверки

```bash
CI=1 npm test -- --watch=false   # Vitest + Angular TestBed, API замокан
npm run test:scripts            # проверки экспорта OpenAPI
npm run e2e                      # Playwright-смоук
npm run format:check             # Prettier
npm run icons:check              # иконки Taiga UI на месте
```

E2E по умолчанию идёт на `http://localhost` (меняется через `E2E_BASE_URL`) и берёт браузер из `CHROME_BIN`; если переменная не задана — `npm run e2e:install`.
