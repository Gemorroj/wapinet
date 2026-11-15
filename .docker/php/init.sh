#!/bin/sh
set -eu

# Temporary application directories under var/tmp are created by Kernel.
mkdir -p var public/static/file public/media/cache
chown -R www-data:www-data var public/static/file public/media/cache frontend/node_modules /var/cache/npm
chmod -R u+rwX,g+rwX,o-rwx var
chmod -R u+rwX,go+rX,go-w public/static/file public/media/cache

if [ -d frontend/.angular ]; then
    chown -R www-data:www-data frontend/.angular
fi

exec runuser -u www-data -- sh -eu -c '
    composer install --no-interaction
    php bin/console doctrine:database:create --if-not-exists --no-interaction
    php bin/console doctrine:migrations:migrate --no-interaction
    php bin/console doctrine:database:create --if-not-exists --env=test --no-interaction
    php bin/console doctrine:migrations:migrate --env=test --no-interaction
'
