# AGENTS.md

Symfony 8.1 / PHP 8.5 проект (сайт wapinet.ru).

## Стек
- Symfony + Twig, Doctrine ORM, MySQL
- EasyAdmin (админка), Webpack Encore (assets)
- Messenger + Scheduler (фоновые задачи), VichUploader (загрузка файлов)

## Структура
- Код в `src/`, тесты в `tests/`, шаблоны в `templates/`, миграции в `migrations/`, ассеты в `assets/`

## Команды
- Тесты: `php vendor/bin/phpunit`
- PHPStan: `php vendor/bin/phpstan analyse`
- PHP-CS-Fixer: `php vendor/bin/php-cs-fixer fix`
- Миграции: `php bin/console doctrine:migrations:migrate`
- Кэш: `php bin/console cache:clear`
