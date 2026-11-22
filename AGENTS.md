# AGENTS.md

Symfony 8.2 / PHP 8.5 (FrankenPHP) + Angular 22 / Taiga UI 5 (сайт wapinet.ru).

## Стек и структура

- Symfony, Doctrine ORM, MySQL, Manticore, Messenger, EasyAdmin; публичный фронтенд — Angular + Taiga UI (`frontend/`), Twig — только админка и письма.
- Backend: `src/`, `tests/`, `templates/`, `migrations/`; frontend: `frontend/src/app/` (`core/`, `shared/`, `pages/`).
- `frontend/src/app/api/generated/` — сгенерированный OpenAPI-клиент, руками не править.
- Документация: `README.md` — обзор, `INSTALL.md` — установка сервера.

## Команды

- Backend: `php vendor/bin/phpunit`, `php vendor/bin/phpstan analyse`, `php vendor/bin/php-cs-fixer fix`.
- БД и кэш: `php bin/console doctrine:migrations:migrate`, `php bin/console cache:clear`.
- Frontend (`cd frontend`): `npm run build`, `CI=1 npm test -- --watch=false`, `npm run format:check`, `npm run api:sync`.
- E2E: `cd frontend && npm run e2e`; браузер из `CHROME_BIN` (локально — Yandex Browser), иначе `npm run e2e:install`.
- Dev-сервер: `cd frontend && $env:API_TARGET='http://localhost'; npm start` (PowerShell).
- Docker (только dev): `docker compose up -d --build --remove-orphans --wait`; порты только на 127.0.0.1 (сайт :80, HMR :4200, письма :8025); CLI — `docker compose exec php ...`, npm — `docker compose exec frontend ...`; настройки приложения — в `.env.local`; индексы Manticore — `docker compose exec manticore indexer --rotate --all`.

## Коммиты

- Conventional Commits.
- Коммит — только после явного согласия автора; законченную задачу не дробить.

## Backend

- Сначала встроенные возможности Symfony; конфигурация — YAML, wiring сервисов — PHP-атрибуты.
- API — `/api`, ошибки в Problem+JSON, аутентификация по Bearer-токену.
- Исключения — в namespace домена (`App\Service\...`); `App\Exception` — только для общих.
- Manticore — только HTTP JSON, без SphinxQL и fallback на БД; после изменения данных — `indexer --rotate --all`.
- Фоновые задачи (превью, метаданные, конвертация) — Messenger: `messenger:consume async`.
- Временные каталоги — только `var/tmp*`; их создаёт `Kernel::buildContainer()`, а не сервисы.

## Frontend

- 4 пробела; стили — компоненты Taiga UI, кастомный CSS только для layout.
- Страницы — отдельные компоненты с явными шаблонами; повторяющиеся сценарии (списки, карточки, подтверждения) — единообразно, по существующим паттернам.
- Уведомления формы — сразу после хлебных крошек, до формы; ошибки полей — общим уведомлением, без подсветки контролов.
- Адрес API — токен `API_URL`, без `environment.ts`; dev-прокси — `API_TARGET`.
- Табы инструмента — отдельные маршруты с одинаковым `data.reuse`; компонент читает `route.data`/`route.paramMap`, не только `snapshot`.
- Опасные действия — `appearance="primary-destructive"` (в Taiga 5 нет `destructive`).

## Правила изменений

- Тесты повторяют структуру `src`: путь файла и namespace совпадают с тестируемым классом.
- Новый код покрывать тестами: backend — PHPUnit, frontend — Vitest/Angular TestBed с моками API.
- Использовать возможности PhpStorm: инспекции, реформат, run configurations, Xdebug.
