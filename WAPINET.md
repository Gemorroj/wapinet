Сайт wapinet.ru
========================

Установка:

Крон задания wapinet:
`php /var/www/wapinet.ru/app/console --env=prod wapinet:tmp:clear "1 day"`
`php /var/www/wapinet.ru/app/console --env=prod wapinet:user:subscriber`

Крон задания sphinx:
`indexer --rotate --all`


Права доступа на запись:
`app/logs`
`app/tmp`
`app/cache`
`web/media/cache/resolve/thumbnail/static`
`web/media/cache/thumbnail/static/avatar`
`web/media/cache/thumbnail/static/file`
`web/static/avatar`
`web/static/file`
