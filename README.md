# Сайт wapinet.ru

##### Лицензия GPL v3

##### Используются:
- Symfony 8.2
- Angular 22 + Taiga UI 5 (SPA, `frontend/`)
- PHP 8.5
- MySQL 8.4
- Manticore
- 7zip
- ffmpeg
- systemd

### Обновление зависимостей + пересборка
```
composer update --with-all-dependencies --no-blocking
cd frontend
npm update
npm run api:sync
npm run build
```

### Frontend (Angular)

```bash
cd frontend
npm ci

# dev-server на :4200; /api и статика проксируются на backend
# API_TARGET по умолчанию http://localhost; при другом локальном адресе задайте его
npm start
# PowerShell: $env:API_TARGET = "http://your-local-host"; npm start

npm run build        # production-сборка в public/app (base href /, assets /app/)
```

По умолчанию `proxy.conf.js` смотрит на `http://localhost`; локальный домен задаётся через `API_TARGET`, в git он не попадает.
Сборка `public/app` коммитится в репозиторий, на проде Node не нужен.
Страницы SPA открываются с корня сайта, статика — из `public/app`.


### Поиск (Manticore)

Приложение работает с Manticore через HTTP JSON (`MANTICORE_HOST:MANTICORE_HTTP_PORT`, по умолчанию `9308`).
Поиск всегда идёт через Manticore: fallback на Doctrine LIKE нет. При недоступном Manticore API файлов возвращает `503`, а страницы поиска gists/users показывают ошибку.
Индексы `files`, `users` и `gist` собираются из MySQL по cron; после изменения схемы нужен `manticore indexer --rotate --all`.


Установка и настройка сервера (Ubuntu 24.04, MySQL, PHP, Angie, Manticore, systemd) — в [`INSTALL.md`](INSTALL.md).

### Лимиты и фоновые задачи

- Загрузка файлов: до 20 файлов за запрос, до 50 МиБ на файл; архиватор — 1000 файлов, 50 МиБ на файл, 250 МиБ на архив.
- Rate limit: `config/packages/rate_limiter.yaml`; при превышении API отдаёт `429` с `Retry-After`, текст для пользователя строит клиент.
- Временные каталоги `var/tmp`, `var/tmp/archiver`, `var/tmp/audio-tags`, `var/tmp/file` создаёт `Kernel` при сборке контейнера; старые файлы чистит `app:tmp-clear`.
- Фоновые задачи (превью, метаданные, конвертация) — через Messenger: `messenger:consume async`.


### Идеи

#### Отложено

- Файлы: проверка на VirusTotal (по кнопке и автоматически), вставка из clipboard, превью изображений в списке, SHA-256 рядом с MD5 (с backfill).
- HTTP-клиент: история, коллекции, окружения, тайминги.
- WHOIS: SPF, DMARC, DKIM по селектору, CAA, DNSSEC, TLS, HTTP-статус.
- Архиватор: выборочная распаковка, поиск по содержимому, preview.
- Аудио-теги: перевести редактор на meta-audio (когда появятся обложки) или ffmpeg.
- Админка: failed-сообщения и scheduler, очистка кэша, переиндексация Manticore, тестовое письмо, разделение `ROLE_ADMIN`/`ROLE_SUPER_ADMIN`.
- Инструменты: конвертер Unix timestamp; Markdown preview (низкий приоритет).
- Политика: ссылки на каналы в MAX.
- Видео-курсы: ссылки на RuTube и VK.
- Страница полезных ссылок: внешние ресурсы для разработчиков (пример — https://externals.io/).
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
