# AGENTS.md

Symfony 8.2 / PHP 8.5 + Angular 22 / Taiga UI 5 (сайт wapinet.ru).

## Стек

- Symfony, Doctrine ORM, MySQL
- Angular + Taiga UI (SPA в `frontend/`)
- Manticore, Messenger, EasyAdmin
- Twig — только админка и письма; публичный фронтенд — Angular

## Структура

- Backend: `src/`, `tests/`, `templates/`, `migrations/` (Doctrine)
- Frontend: `frontend/src/app/` (`core/`, `shared/`, `pages/`)
- `frontend/src/app/api/generated/` — сгенерированный OpenAPI-клиент, руками не править
- Документация: `README.md` — обзор, `INSTALL.md` — установка сервера

## Команды

- Тесты backend: `php vendor/bin/phpunit`
- PHPStan: `php vendor/bin/phpstan analyse`
- PHP-CS-Fixer: `php vendor/bin/php-cs-fixer fix`
- Миграции БД: `php bin/console doctrine:migrations:migrate`
- Кэш: `php bin/console cache:clear`
- Frontend: `cd frontend && npm run build`
- Тесты frontend: `cd frontend && CI=1 npm test -- --watch=false`
- Форматирование frontend: `cd frontend && npm run format:check`
- OpenAPI-клиент: `cd frontend && npm run api:sync`
- E2E: `cd frontend && npm run e2e`; браузер из `CHROME_BIN` (локально — Yandex Browser), иначе `npm run e2e:install`
- Dev-сервер: `cd frontend && $env:API_TARGET='http://localhost'; npm start` (PowerShell)
- Docker: `docker-compose.yml` (mysql, manticore, php-fpm, messenger, angie)

## Коммиты

- Сообщения — Conventional Commits.
- Агент коммитит только после явного согласия автора и не дробит законченную задачу на части.

## Backend

- Сначала встроенные возможности Symfony; конфигурация — YAML, wiring сервисов — PHP-атрибуты.
- API — `/api`, ошибки в Problem+JSON, аутентификация по Bearer-токену.
- Исключения — в namespace домена (`App\Service\...`); `App\Exception` — только для общих.
- Manticore — только HTTP JSON, без SphinxQL и fallback на БД; после изменения данных — `indexer --rotate --all`.
- Фоновые задачи (превью, метаданные, конвертация) — через Messenger: `messenger:consume async`.
- Временные каталоги — только в `var/tmp*`; их создаёт `Kernel::buildContainer()`, а не сервисы.

## Frontend

- 4 пробела; стили — компоненты Taiga UI, кастомный CSS только для layout.
- Страницы — отдельные компоненты с явными шаблонами; повторяющиеся сценарии (списки, карточки, подтверждения) оформлять единообразно и переиспользовать существующие паттерны.
- Уведомления формы — сразу после хлебных крошек, до формы; ошибки полей — общим уведомлением, без подсветки контролов.
- Адрес API — токен `API_URL`, без `environment.ts`; dev-прокси — `API_TARGET`.
- Табы инструмента — отдельные маршруты с одинаковым `data.reuse`; компонент читает `route.data`/`route.paramMap`, а не только `snapshot`.
- Опасные действия — `appearance="primary-destructive"` (в Taiga 5 нет `destructive`).

## Правила изменений

- Тесты повторяют структуру `src`: путь файла и namespace совпадают с тестируемым классом.
- Новый код покрывать тестами: backend — PHPUnit, frontend — Vitest/Angular TestBed с моками API.
- Использовать возможности PhpStorm: инспекции, реформат, run configurations, Xdebug.
