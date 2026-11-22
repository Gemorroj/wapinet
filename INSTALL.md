# Установка

Актуально для Ubuntu 24.04. Обзор проекта — в [`README.md`](README.md).

Стек: FrankenPHP (worker mode), PHP, MySQL, Manticore, systemd.

## Система

```bash
apt update && sudo apt dist-upgrade && sudo apt autoremove --purge
apt install software-properties-common curl git unzip htop mc
hostnamectl set-hostname wapinet.ru
timedatectl set-timezone UTC

# edit /etc/hosts to associate domain to ip address without dns requests. see https://www.linode.com/docs/guides/getting-started/#update-your-systems-hosts-file
# edit /etc/ssh/sshd_config - set `Port 2200`
# edit /root/.ssh/authorized_keys - add public key
reboot
```

## MySQL

```bash
cd /
wget https://dev.mysql.com/get/mysql-apt-config_0.8.40-1_all.deb
dpkg -i mysql-apt-config_0.8.40-1_all.deb
apt update
apt install mysql-client mysql-server

mysql_secure_installation
```

```bash
cat > /etc/mysql/mysql.conf.d/z_wapinet.cnf <<'EOF'
[mysqld]
bind-address = 127.0.0.1,::1
mysqlx-bind-address = 127.0.0.1,::1
skip-log-bin
skip-external-locking
skip-name-resolve

innodb_file_per_table=1
max_connections=50
innodb_flush_log_at_trx_commit=2

innodb_buffer_pool_size=256M
innodb_buffer_pool_instances=1
innodb_log_buffer_size=32M

key_buffer_size=0
innodb_flush_method=O_DIRECT

table_open_cache=2000
tmp_table_size=48M
max_heap_table_size=48M
join_buffer_size=3M

innodb_fast_shutdown=0
EOF

systemctl restart mysql
```

## Manticore

```bash
apt install default-libmysqlclient-dev
curl -O -L https://repo.manticoresearch.com/manticore-repo.noarch.deb
dpkg -i manticore-repo.noarch.deb
rm manticore-repo.noarch.deb
apt update
apt install manticore manticore-extra
```

```bash
cat > /etc/manticoresearch/manticore.conf <<'EOF'
common {
    plugin_dir = /usr/local/lib/manticore
}
indexer
{
    mem_limit = 128M
}
searchd
{
    # listen = localhost:9312
    listen = localhost:9306:mysql
    listen = localhost:9308:http
    log = /var/log/manticore/searchd.log
    query_log = /var/log/manticore/query.log
    query_log_format = sphinxql
    pid_file = /run/manticore/searchd.pid
    # data_dir = /var/lib/manticore
    binlog_path = # disable logging

    qcache_max_bytes = 32M
    qcache_thresh_msec = 1000
    qcache_ttl_sec = 600
    max_children = 50
    max_packet_size = 32M
    read_timeout = 10
    client_timeout = 30
    predicted_time_costs = doc=0.1, hit=0.01, skip=0.001, match=0.001
}

source config
{
    type = mysql
    sql_host = localhost
    sql_user = wapinet
    sql_pass = password
    sql_db = wapinet
    sql_port = 3306
    sql_query_pre = SET NAMES utf8mb4
}

source files:config
{
    sql_query = \
        SELECT f.id, \
        f.description, \
        f.original_file_name, \
        f.mime_type, \
        f.user_id, \
        f.count_views, \
        UNIX_TIMESTAMP(f.created_at) AS created_at_ts, \
        ( \
            SELECT GROUP_CONCAT(t.name SEPARATOR " ") \
            FROM tag AS t \
            WHERE t.id IN(SELECT file_tags.tag_id FROM file_tags WHERE file_tags.file_id = f.id) \
        ) AS tag_name \
        FROM file AS f \
        WHERE f.password IS NULL \
        AND f.hidden = 0

    sql_field_string = description
    sql_field_string = original_file_name
    sql_field_string = tag_name
    sql_attr_string = mime_type
    sql_attr_uint = user_id
    sql_attr_uint = count_views
    sql_attr_timestamp = created_at_ts
    sql_attr_multi = uint tag_id from query; \
        SELECT ft.file_id AS id, ft.tag_id FROM file_tags ft
}

index files
{
    type = plain
    source = files
    path = /var/lib/manticore/files
    morphology = stem_ru, stem_en, soundex
    html_strip = 0
    min_infix_len = 3
    min_word_len = 2
    expand_keywords = 1
    index_exact_words = 1
    charset_table = 0..9, english, russian, _
}

source users:config
{
    sql_query = \
        SELECT u.id, \
        u.username, \
        u.email, \
        u.info \
        FROM user AS u \
        WHERE u.enabled = 1

    sql_field_string = username
    sql_field_string = email
    sql_field_string = info
}

index users
{
    type = plain
    source = users
    path = /var/lib/manticore/users
    morphology = stem_ru, stem_en, soundex
    html_strip = 0
    min_infix_len = 3
    min_word_len = 2
    expand_keywords = 1
    index_exact_words = 1
    charset_table = 0..9, english, russian, _
}

source gist:config
{
    sql_query = \
        SELECT g.id, \
        g.subject, \
        g.body, \
        g.user_id, \
        UNIX_TIMESTAMP(g.created_at) AS created_at_ts \
        FROM gist AS g

    sql_field_string = subject
    sql_field_string = body
    sql_attr_uint = user_id
    sql_attr_timestamp = created_at_ts
}

index gist
{
    type = plain
    source = gist
    path = /var/lib/manticore/gist
    morphology = stem_ru, stem_en, soundex
    html_strip = 0
    min_infix_len = 3
    min_word_len = 2
    expand_keywords = 1
    index_exact_words = 1
    charset_table = 0..9, english, russian, _
}
EOF

cat > /etc/cron.d/manticore <<'EOF'
0 2 * * * manticore /usr/bin/indexer --rotate --all >> /var/log/manticore/indexer.log 2>&1
EOF

systemctl enable manticore
systemctl restart manticore

# После импорта данных и после каждого изменения данных:
# sudo -u manticore /usr/bin/indexer --rotate --all
```

## FrankenPHP

```bash
VERSION=85
curl -fsSL "https://pkg.henderkes.com/api/packages/${VERSION}/debian/repository.key" \
  -o "/etc/apt/keyrings/static-php${VERSION}.asc"
echo "deb [signed-by=/etc/apt/keyrings/static-php${VERSION}.asc] https://pkg.henderkes.com/api/packages/${VERSION}/debian php-zts main" \
  | tee /etc/apt/sources.list.d/static-php${VERSION}.list
apt update
apt install frankenphp
frankenphp version
systemctl stop frankenphp

apt install \
  php-zts-apcu \
  php-zts-curl \
  php-zts-gd \
  php-zts-intl \
  php-zts-mbstring \
  php-zts-mysql \
  php-zts-xml \
  php-zts-zip \
  php-zts-sodium \
  php-zts-opcache
```

Прослойка для `php`:

```bash
cat > /usr/local/bin/php <<'PHP'
#!/usr/bin/env bash
set -euo pipefail

args=()
passthrough=0
expect_d_value=0

for arg in "$@"; do
    if (( passthrough )); then
        args+=("$arg")
        continue
    fi

    if [ "$arg" = "--" ]; then
        passthrough=1
        args+=("$arg")
        continue
    fi

    if (( expect_d_value )); then
        expect_d_value=0
        continue
    fi

    case "$arg" in
        -d)
            expect_d_value=1
            ;;
        -d\ *|-d*)
            ;;
        *)
            args+=("$arg")
            ;;
    esac
done

exec /usr/bin/frankenphp php-cli "${args[@]}"
PHP
chmod 755 /usr/local/bin/php
```

`frankenphp php-cli` не поддерживает `-d`, поэтому wrapper их отбрасывает; `--` и аргументы после него передаются как есть.

Для PECL-расширений, которых нет в `php-zts-*`, можно использовать PIE:

```bash
apt install pie-zts
pie-zts install vendor/package
```

Проверка PHP и расширений:

```bash
php -v
php --ini
php -r 'foreach (["apcu","gd","intl","pdo_mysql","sodium","simplexml","zip","curl","mbstring","opcache","pcntl"] as $e) printf("%s=%s\n", $e, extension_loaded($e) ? "ok" : "MISSING");'
```

Пути deb-пакета: `/etc/php-zts/php.ini`, `/etc/php-zts/conf.d/*.ini`, `/etc/frankenphp/Caddyfile`. Если `php --ini` показывает другие пути — использовать их.

Composer:

```bash
cd /var/www
curl -L -o composer.phar https://getcomposer.org/download/latest-stable/composer.phar
chmod 755 composer.phar
```

## GeoIP, 7zip, ffmpeg

```bash
cd /var/www
curl -L -o GeoLite2-City.mmdb https://github.com/P3TERX/GeoLite.mmdb/raw/download/GeoLite2-City.mmdb
curl -L -o GeoLite2-ASN.mmdb https://github.com/P3TERX/GeoLite.mmdb/raw/download/GeoLite2-ASN.mmdb

cd /opt
mkdir 7z2606-linux-x64
cd 7z2606-linux-x64
curl -fL -o 7z.tar.xz "https://github.com/ip7z/7zip/releases/download/26.06/7z2606-linux-x64.tar.xz"
tar xJf 7z.tar.xz
rm -f 7z.tar.xz
/opt/7z2606-linux-x64/7zz i
ln -sf /opt/7z2606-linux-x64/7zz /usr/local/bin/7zz

add-apt-repository ppa:ubuntuhandbook1/ffmpeg9
apt update
apt install ffmpeg
```

## Установка сайта

```bash
cd /var/www
git clone https://github.com/Gemorroj/wapinet.git
cd wapinet

cp .env.dist .env
# edit .env: APP_ENV=prod, APP_DEBUG=0, DATABASE_URL, MANTICORE_HOST,
# MAILER_DSN, PHP_PATH=/usr/local/bin/php, P7Z_PATH=/usr/local/bin/7zz и т.д.
```

```bash
install -d /etc/php-zts/conf.d
cat > /etc/php-zts/conf.d/99-wapinet.ini <<'INI'
memory_limit = -1
max_execution_time = 0
max_input_time = -1

post_max_size = 55M
upload_max_filesize = 50M

date.timezone = UTC
expose_php = Off
display_errors = Off
log_errors = On

session.save_path = /var/www/wapinet/var/sessions

realpath_cache_size = 16M
realpath_cache_ttl = 600

opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 18
opcache.max_accelerated_files = 100000
opcache.validate_timestamps = 0

apc.enabled = 1
apc.shm_size = 64M
apc.enable_cli = 0
INI

install -d /etc/frankenphp
cat > /etc/frankenphp/Caddyfile <<'CADDY'
{
    email robot@wapinet.ru

    frankenphp {
        num_threads 2
        max_threads 12
        max_wait_time 30s

        worker {
            file /var/www/wapinet/public/index.php
            num 8
            env FRANKENPHP_WORKER 1
            env FRANKENPHP_LOOP_MAX 500
        }
    }
}

wapinet.ru {
    root * /var/www/wapinet/public
    encode zstd br gzip

    header {
        Strict-Transport-Security "max-age=31536000; includeSubDomains"
        X-Frame-Options "DENY"
        X-Content-Type-Options "nosniff"
        -Server
    }

    request_body {
        max_size 55MiB
    }

    log {
        format json
    }

    @hidden {
        path_regexp hidden (^|/)\.
        not path /.well-known/*
    }
    respond @hidden 404

    @forum path_regexp forum ^/forum(.*)$
    redir @forum https://forum.wapinet.ru{re.forum.1} 301

    @php path /api /api/* /admin /admin/* /media/cache/resolve /media/cache/resolve/* /index.php /_profiler /_profiler/* /_wdt /_wdt/*
    handle @php {
        php_server {
            root /var/www/wapinet/public
            try_files {path} index.php
        }
    }

    @static path /static /static/*
    handle @static {
        log_skip
        header Content-Disposition "attachment"
        header X-Content-Type-Options "nosniff"
        header Cache-Control "public, max-age=2592000"
        file_server
    }

    @appIndex path /app /app/ /app/index.html
    handle @appIndex {
        header Cache-Control "no-cache"
        file_server
    }

    @assets path /app/* /bundles/* /resources/* /media/*
    handle @assets {
        log_skip
        header Cache-Control "public, max-age=2592000"
        file_server
    }

    handle {
        try_files {path} /app/index.html
        header Cache-Control "no-cache"
        file_server
    }
}

www.wapinet.ru {
    redir https://wapinet.ru{uri} 301
}
CADDY


install -d /etc/systemd/system/frankenphp.service.d
cat > /etc/systemd/system/frankenphp.service.d/wapinet.conf <<'UNIT'
[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/wapinet

Environment=APP_ENV=prod
Environment=APP_DEBUG=0
Environment=XDG_DATA_HOME=/var/lib/frankenphp
Environment=XDG_CONFIG_HOME=/var/lib/frankenphp
Environment=GODEBUG=cgocheck=0

MemoryMax=1800M
TimeoutStopSec=60s
Restart=always
RestartSec=3s

AmbientCapabilities=CAP_NET_BIND_SERVICE
LimitNOFILE=1048576
UNIT

install -d -o www-data -g www-data /var/lib/frankenphp
install -d -o www-data -g www-data -m 0700 var/sessions

systemctl daemon-reload
```

Зависимости и кэш:

```bash
cd /var/www/wapinet
php ../composer.phar install --no-dev --optimize-autoloader
php bin/console about
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

Права на записываемые каталоги:

```bash
mkdir -p public/static/file \
  public/media/cache/resolve/thumbnail/static \
  public/media/cache/thumbnail/static
chown -R www-data:www-data var public/static/file public/media/cache
chmod -R u+rwX,g+rwX,o-rwx var
chmod -R u+rwX,go+rX,go-w public/static/file public/media/cache
```

## СУБД

```bash
mysql -u root -p
```

```sql
CREATE DATABASE IF NOT EXISTS wapinet
    CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
CREATE USER 'wapinet'@'localhost' IDENTIFIED BY 'password';
GRANT ALL ON wapinet.* TO 'wapinet'@'localhost';
GRANT SELECT ON forum.* TO 'wapinet'@'localhost';
QUIT;
```

```bash
mysql -u wapinet -p wapinet < wapinet.sql

# Первичная индексация Manticore и загрузка таблиц в searchd
sudo -u manticore /usr/bin/indexer --rotate --all
systemctl restart manticore
```

## Запуск FrankenPHP

DNS для `wapinet.ru`/`www.wapinet.ru` уже должен указывать на сервер, порты 80/443 открыты:

```bash
systemctl enable frankenphp
systemctl start frankenphp
systemctl status frankenphp
journalctl -u frankenphp -f
```

Проверка HTTPS и worker mode:

```bash
curl -I https://wapinet.ru
curl -s https://wapinet.ru/api/health
```

Если используется firewall:

```bash
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 443/udp
```

## Messenger и scheduler

```bash
cp bin/messenger/messenger.service.dist bin/messenger/messenger.service
cp bin/messenger/scheduler.service.dist bin/messenger/scheduler.service
# edit bin/messenger/messenger.service
# edit bin/messenger/scheduler.service
ln -s /var/www/wapinet/bin/messenger/messenger.service /etc/systemd/system/messenger.service
ln -s /var/www/wapinet/bin/messenger/scheduler.service /etc/systemd/system/scheduler.service
systemctl daemon-reload
systemctl enable --now messenger scheduler
```


## Обновление

```bash
cd /var/www/wapinet
git pull
php ../composer.phar install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
chown -R www-data:www-data var
chmod -R u+rwX,g+rwX,o-rwx var
systemctl restart messenger scheduler
systemctl restart frankenphp
```

`systemctl reload frankenphp` перезагружает только Caddyfile. После изменения PHP-кода нужен `restart`: в `opcache.validate_timestamps=0` старый код иначе останется в OPcache/worker’ах.
