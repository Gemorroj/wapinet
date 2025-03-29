# Сайт wapinet.ru

##### Лицензия GPL v3

##### Используются:
- Symfony 7.2
- Jquery Mobile
- PHP 8.4
- MySQL 8.0
- Manticore
- 7zip
- ffmpeg
- systemd


### Базовая установка (актуально для Ubuntu 24.04)
```bash
apt update && sudo apt dist-upgrade && sudo apt autoremove --purge
apt install software-properties-common
add-apt-repository ppa:ondrej/php
add-apt-repository ppa:ondrej/nginx
apt update && apt dist-upgrade
hostnamectl set-hostname wapinet.ru
timedatectl set-timezone UTC

# edit /etc/hosts to associate domain to ip address without dns requests. see https://www.linode.com/docs/guides/getting-started/#update-your-systems-hosts-file
# edit /etc/ssh/sshd_config - set `Port 2200`
# edit /root/.ssh/authorized_keys - add public key
reboot
```

```bash
apt install htop mc git unzip
apt install mysql-client mysql-server
apt install nginx
systemctl enable nginx
apt install php8.4-fpm php8.4-curl php8.4-gd php8.4-intl php8.4-mbstring php8.4-mysql php8.4-xml php8.4-zip php8.4-apcu

mysql_secure_installation
echo '[mysqld]
default-authentication-plugin=mysql_native_password

skip-log-bin
skip-external-locking
skip-name-resolve

transaction_write_set_extraction=OFF

innodb_file_per_table=1
max_connections=50
innodb_flush_log_at_trx_commit=2
innodb_buffer_pool_size=512M
innodb_buffer_pool_instances=1
innodb_log_file_size=76M
key_buffer_size=0
innodb_flush_method = O_DIRECT


table_open_cache=2000
tmp_table_size=76M
max_heap_table_size=76M
join_buffer_size = 2M

innodb_fast_shutdown = 0
' > /etc/mysql/mysql.conf.d/z_wapinet.cnf

# manticore
apt install default-libmysqlclient-dev
curl -O -L https://repo.manticoresearch.com/manticore-repo.noarch.deb
dpkg -i manticore-repo.noarch.deb
rm manticore-repo.noarch.deb
apt install manticore manticore-extra
systemctl enable manticore

echo 'common {
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
    # listen = localhost:9308:http
    log = /var/log/manticore/searchd.log
    query_log = /var/log/manticore/query.log
    query_log_format = sphinxql
    pid_file = /run/manticore/searchd.pid
    # data_dir = /var/lib/manticore
    binlog_path = # disable logging
}

source config
{
    type = mysql
    sql_host = localhost
    sql_user = wapinet
    sql_pass = xgh466*fhjG
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
    sql_attr_timestamp = created_at_ts
}

index files
{
    type = plain
    source = files
    path = /var/lib/manticore/files
    morphology = stem_enru, soundex, metaphone
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
    morphology = stem_enru, soundex, metaphone
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
        UNIX_TIMESTAMP(g.created_at) AS created_at_ts \
        FROM gist AS g

    sql_field_string = subject
    sql_field_string = body
    sql_attr_timestamp = created_at_ts
}

index gist
{
    type = plain
    source = gist
    path = /var/lib/manticore/gist
    morphology = stem_enru, soundex, metaphone
    html_strip = 0
    min_infix_len = 3
    min_word_len = 2
    expand_keywords = 1
    index_exact_words = 1
    charset_table = 0..9, english, russian, _
}' > /etc/manticoresearch/manticore.conf

echo '0 2 * * * indexer --rotate --all  > /dev/null 2>&1' -> /var/spool/cron/crontabs/manticore
chown manticore:crontab /var/spool/cron/crontabs/manticore
chmod 600 /var/spool/cron/crontabs/manticore


# composer
cd /var/www
curl -L -o composer.phar https://getcomposer.org/download/latest-stable/composer.phar
chmod 755 composer.phar

# geoip database
cd /var/www
curl -L -o GeoLite2-Country.mmdb https://github.com/P3TERX/GeoLite.mmdb/raw/download/GeoLite2-Country.mmdb

# 7zip
cd /opt
mkdir 7z2409-linux-x64
cd /opt/7z2409-linux-x64
curl -O -L https://7-zip.org/a/7z2409-linux-x64.tar.xz
tar xJvf 7z2409-linux-x64.tar.xz
rm -f 7z2409-linux-x64.tar.xz
/opt/7z2409-linux-x64/7zz i

# ffmpeg - https://ubuntuhandbook.org/index.php/2024/04/ffmpeg-7-0-ppa-ubuntu/
add-apt-repository ppa:ubuntuhandbook1/ffmpeg7
apt install ffmpeg
```

```bash
# edit /etc/php/8.4/fpm/php.ini & /etc/php/8.4/cli/php.ini
# cgi.fix_pathinfo=0
# memory_limit = 256M
# date.timezone = "UTC"
# post_max_size = 50M
# upload_max_filesize = 50M
# opcache.enable=1
# opcache.enable_cli=1
# opcache.memory_consumption=256
# opcache.interned_strings_buffer=18
# opcache.max_accelerated_files=100000
# opcache.validate_timestamps=0
# [apcu]
# apc.shm_size=64M
# apc.enabled = on
# apc.enable_cli = on

# edit /etc/php/8.4/fpm/pool.d/www.conf
# listen.allowed_clients = 127.0.0.1
# edit pm.* settings for performance

# edit /etc/nginx/nginx.conf
# server_tokens off;
# gzip  on;
# gzip_comp_level 2;
# gzip_min_length 40;
# gzip_types text/css text/plain application/json text/javascript application/javascript text/xml application/xml application/xml+rss application/x-font-ttf application/x-font-opentype application/vnd.ms-fontobject image/svg+xml image/x-icon font/ttf font/opentype;
```

```bash
server {
    listen 80;

    server_name wapinet.ru www.wapinet.ru;
	return 301 https://$server_name$request_uri;
}

server {
    if ($request_uri ~ "/forum/(.*)") {
        return 301 https://forum.$server_name/$1;
    }

    location ~ /\.well-known\/acme-challenge {
        allow all;
    }
    location ~ /\. {
        deny all;
    }

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_certificate /root/.acme.sh/wapinet.ru/fullchain.cer;
    ssl_certificate_key /root/.acme.sh/wapinet.ru/wapinet.ru.key;

    charset utf-8;
    listen 443 ssl http2;
    client_max_body_size 50m;

    server_name wapinet.ru www.wapinet.ru;
    root /var/www/wapinet/public;

    error_log /var/log/nginx/wapinet.error.log;
    access_log /var/log/nginx/wapinet.access.log;

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";
    add_header X-Frame-Options "DENY";
    add_header X-Content-Type-Options nosniff;

    # Кэширование
    location = /favicon.ico {
        access_log off;
        expires 30d;
    }
    location = /robots.txt {
        access_log off;
        expires 30d;
    }
    location = /apple-touch-icon.png {
        access_log off;
        expires 30d;
    }
    location /bundles/ {
        access_log off;
        expires 30d;
    }
    location /build/ {
        access_log off;
        expires 30d;
    }
    location /media/ {
        access_log off;
        expires 30d;

        try_files $uri /index.php$is_args$args;
    }
    location ~ ^/static/ {
        # Скачивание всех файлов (в т.ч. и txt, html и проч. в обменнике), чтобы потенциальный html/js код не выполнился в браузере
        add_header Content-Disposition "attachment";
        access_log off;
        expires 30d;
    }


    location / {
        # try to serve file directly, fallback to index.php
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
       # When you are using symlinks to link the document root to the
       # current version of your application, you should pass the real
       # application path instead of the path to the symlink to PHP
       # FPM.
       # Otherwise, PHP OPcache may not properly detect changes to
       # your PHP files (see https://github.com/zendtech/ZendOptimizerPlus/issues/126
       # for more information).
       fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
       fastcgi_param DOCUMENT_ROOT $realpath_root;
       # Prevents URIs that include the front controller. This will 404:
       # http://domain.tld/index.php/some-path
       # Remove the internal directive to allow URIs like this
       internal;
   }

    #location ~ \.php$ {
    #    return 404;
    #}
}' > /etc/nginx/sites-available/wapinet.ru.conf
ln -s /etc/nginx/sites-available/wapinet.ru.conf /etc/nginx/sites-enabled/wapinet.ru.conf
```

### СУБД
```bash
mysql -u root -p
CREATE USER 'wapinet'@'localhost' IDENTIFIED BY 'password';
GRANT ALL ON wapinet.* TO 'wapinet'@'localhost';
GRANT SELECT ON forum.* TO 'wapinet'@'localhost';
quit
mysql -u wapinet -p wapinet < wapinet.sql
```

### Установка сайта
```bash
cd /var/www
git clone https://github.com/Gemorroj/wapinet.git
cd wapinet
cp .env.dist .env
# edit .env
../composer.phar install --no-dev --optimize-autoloader --apcu-autoloader
rm -rf ./var/cache/*
rm -rf ./var/log/*
rm -rf ./var/tmp/*
service php-fpm restart

cp bin/messenger/messenger.service.dist bin/messenger/messenger.service
cp bin/messenger/scheduler.service.dist bin/messenger/scheduler.service
# edit bin/messenger/messenger.service
# edit bin/messenger/scheduler.service
ln -s /var/www/wapinet/bin/messenger/messenger.service /etc/systemd/system/messenger.service
ln -s /var/www/wapinet/bin/messenger/scheduler.service /etc/systemd/system/scheduler.service
systemctl daemon-reload
systemctl enable messenger
systemctl enable scheduler

chmod 777 ./var/log
chmod 777 ./var/tmp
chmod 777 ./var/cache
chmod 777 ./public/media/cache/resolve/thumbnail/static
chmod 777 ./public/media/cache/thumbnail/static
chmod 777 ./public/static/file
```

### SSL сертификаты
##### Установка
```bash
apt install socat
curl https://get.acme.sh | sh -s email=wapinet@mail.ru
acme.sh --issue -d wapinet.ru
systemctl restart nginx
```
##### Обновление
```bash
acme.sh --upgrade
acme.sh --renew-all --force
service nginx restart
```


### TODO:
- ! проверка файлов на virustotal (возможно автоматическая) в файлообменнике (https://docs.virustotal.com/reference/files-scan + https://docs.virustotal.com/reference/analysis)
- !!! Переделать интерфейс на vue/react/angular (огромная задача. jquery mobile официально мертв - https://blog.jquerymobile.com/2021/10/07/jquery-maintainers-continue-modernization-initiative-with-deprecation-of-jquery-mobile/)
- Переделать редактор аудиотегов на https://github.com/duncan3dc/meta-audio/issues/3 (когда будут картинки)
- ! Сделать возможность в обфускаторе загружать файлы
- !! Актуализировать мобильные коды. Найти новые для разных андроидов.
- Оболочка над nmap https://github.com/willdurand/nmap (работает медленно и выдает мало информации)
- Проверка на спамеров http://www.stopforumspam.com/usage (https://github.com/Gemorroj/StopSpam)
