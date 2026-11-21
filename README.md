# Сайт wapinet.ru

##### Лицензия GPL v3

##### Используются:
- Symfony 8.2
- Angular 22 + Taiga UI 5 (SPA, `frontend/`)
- PHP 8.5
- FrankenPHP (Caddy) в worker mode
- MySQL 8.4
- Manticore
- 7zip
- ffmpeg
- systemd

### Локальная разработка в Docker

`docker-compose.yml` — только для dev. Нужен `.env.local` (не коммитится): скопируйте `.env.dist` и укажите контейнерные хосты:

```dotenv
DATABASE_URL="mysql://root:@mysql:3306/wapinet?serverVersion=8.4"
MANTICORE_HOST=manticore
MAILER_DSN=smtp://mailpit:1025
PHP_PATH=/usr/local/bin/php
P7Z_PATH=/usr/local/bin/7zz
```

Compose задаёт только контейнерные переменные; остальное приложение читает из `.env.local` (реальный env перекрывает dotenv, поэтому дублировать настройки в compose нельзя). В dev MySQL — `root` без пароля, как в `.env.dist`; имя БД `wapinet` указано ещё и в `.docker/manticore/manticore.conf` (индексатор). FrankenPHP в контейнере `php` отдаёт SPA, API и `/admin`; отдельного nginx нет.

```bash
docker compose up -d --build --remove-orphans
```

`init` при старте выставляет владельцев каталогов, ставит Composer-зависимости и миграции обеих БД; остальные сервисы ждут его (логи — `docker compose logs init`, повтор — `docker compose run --rm init`). PHP и Node работают от владельца исходников (`1000:1000`); другой UID: `LOCAL_UID=$(id -u) LOCAL_GID=$(id -g) docker compose up -d --build`.

Порты только на `127.0.0.1`: `:80` — FrankenPHP с собранным SPA, API и `/admin`; `:4200` — Angular с HMR; `:8025` — Mailpit; `:3306` — MySQL (root без пароля).

Тестовая БД создаётся на пустом volume, для существующего — `docker compose exec mysql sh /docker-entrypoint-initdb.d/10-test-database.sh`.

### Сборка и проверки

```bash
docker compose exec php composer update --with-all-dependencies --no-blocking
docker compose exec frontend ./node_modules/.bin/ng update --allow-dirty
docker compose exec frontend npm update
docker compose restart frontend

docker compose exec php php vendor/bin/phpunit
docker compose exec php php vendor/bin/phpstan analyse
docker compose exec -e CI=1 frontend npm test -- --watch=false
docker compose exec frontend npm run test:scripts
docker compose exec frontend npm run format:check

docker compose exec frontend npm run api:sync # OpenAPI → TS-клиент, backend должен быть запущен
docker compose exec frontend npm run build # собирает public/app
```

Контейнер `frontend` при старте сам ставит зависимости, если `node_modules` пуст или изменился `package-lock.json`. После правок фоновых обработчиков — `docker compose restart messenger scheduler`.

### Поиск (Manticore)

Приложение работает с Manticore только через HTTP JSON (`MANTICORE_HOST:MANTICORE_HTTP_PORT`) — fallback на Doctrine LIKE нет: при недоступном Manticore API файлов отдаёт `503`, поиск gists/users показывает ошибку. Таблицы `files`, `users`, `gist` создаются при старте dev-контейнера, индексы обновляются вручную: `docker compose exec manticore indexer --rotate --all`.

Установка и настройка сервера (Ubuntu 24.04, MySQL, FrankenPHP, Manticore, systemd) — в [`INSTALL.md`](INSTALL.md).

### Лимиты и фоновые задачи

- Загрузка файлов: до 50 МиБ; архиватор — до 20 файлов за запрос, суммарно до 50 МиБ; в архиве — до 1000 файлов, 50 МиБ на файл, 250 МиБ на архив.
- Rate limit: `config/packages/rate_limiter.yaml`; при превышении API отдаёт `429` с `Retry-After`, текст для пользователя строит клиент.
- Временные каталоги `var/tmp`, `var/tmp/archiver`, `var/tmp/audio-tags`, `var/tmp/file` создаёт `Kernel` при сборке контейнера; старые файлы чистит `app:tmp-clear`.
- Фоновые задачи (превью, метаданные, конвертация) — через Messenger: `messenger:consume async`.
- Периодические задачи — отдельный worker `messenger:consume scheduler_default`. В dev состояние расписания хранится на диске и сохраняется при перезапуске worker; пропущенная задача выполняется один раз.

### Идеи

#### Отложено

- Политика: ссылки на каналы в MAX.
- Видео-курсы: ссылки на RuTube и VK.
- Страница полезных ссылок: внешние ресурсы для разработчиков (пример — https://externals.io/).
- Аудио-теги: переделать на медиа комбайн полностью в браузере - TagLib-wasm + MediaBunny
- Перенести форум. Упростить и мигрировать в основную ветку wapinet.
- Файлы: проверка на VirusTotal (по кнопке и автоматически), вставка из clipboard, превью изображений в списке, SHA-256 рядом с MD5 (с backfill).
- WHOIS: SPF, DMARC, DKIM по селектору, CAA, DNSSEC, TLS, HTTP-статус.
- HTTP-клиент: история, коллекции, окружения, тайминги.
- Архиватор: выборочная распаковка, поиск по содержимому, preview.
- Админка: failed-сообщения и scheduler, очистка кэша, переиндексация Manticore, тестовое письмо, разделение `ROLE_ADMIN`/`ROLE_SUPER_ADMIN`.
- Инструменты: конвертер Unix timestamp; Markdown preview (низкий приоритет).
- BBCode → Markdown: миграция легаси-разметки. В базе ~1344 записи с BBCode из 10383 (гостевая 1308, блоги 17,
  новости 10, описания файлов 5, «О себе» 4); используются `[quote=Автор]` (901), `[url]` (389), `[img]` (20),
  `[code]` (11), `[hide]` (7), `[b]/[i]/[s]/[color]/[br]` (15), `[youtube]` (1); списков и таблиц нет. План:
  колонка `format` (`bbcode`/`markdown`) в таблицах, `ContentRenderer` с двумя рендерерами (`league/commonmark`
  + GFM), новые записи в Markdown, старые рендерятся как сейчас; затем одноразовый конвертер по AST
  (`Xbbcode::getTree()`) с `--dry-run`, отчётом расхождений и сохранением оригинала в `legacy_source`; после —
  переиндексация Manticore и удаление BBCode-рендерера. Маппинг: `[url=адрес]текст[/url]` → `[текст](адрес)`,
  `[url]адрес[/url]` → `<адрес>`, `[img]адрес[/img]` → `![](адрес)`, `[code=php]` → огороженный блок с языком,
  `[hide]` → `<details>`, `[quote=Автор]` → `> **Автор:**`. Отдельное решение нужно по автору цитаты: в Markdown
  это не стандартная конструкция, а 901 запись — в основном ответы через «Ответить».

#### Отклонено

- JWT decoder/encoder — не нужен.
- Отдельный каталог `/tools` — группировки на главной достаточно.
- Главная: дата последней новости и правило «показывать, только если не старше года» — сайт обновляется редко, дата в блоке не нужна.
- Телефонные коды — раздел удалён, актуализировать нечего.
- Browser Info: история/сравнение визитов, экспорт, рекомендации по приватности.
- Главная: поиск/command palette, недавние, избранное.
- Админка: 2FA, журнал действий, список сессий.
- Социальные функции, комментарии, рейтинги.
- Password generator, QR, UUID отдельными разделами.
- Сетевой сканер и оболочка над nmap.
- Полная Unicode Character Database в SPA.
- Экспорт отчёта WHOIS.
