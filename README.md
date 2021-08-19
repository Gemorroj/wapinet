# Сайт wapinet.ru

##### Лицензия GPL v3

##### Используются:
- Symfony 5.2
- Jquery Mobile
- PHP 8.0
- MySQL 8.0
- Manticore
- p7zip
- ffmpeg


### Базовая установка (актуально для Centos 8 Stream)
- Отключить `selinux`
```bash
sed -i 's/^SELINUX=.*/SELINUX=disabled/g' /etc/selinux/config
reboot
```
- Перевести на Stream
```bash
dnf upgrade
dnf install centos-release-stream
dnf swap centos-{linux,stream}-repos
dnf distro-sync
dnf autoremove
reboot
```
- Установить дополнительные репозитории `powertools`, `epel`, `remi`, `nginx`, `mysql`, `manticore`
```bash
dnf install dnf-plugins-core
dnf config-manager --set-enabled powertools
dnf install epel-release
dnf install https://rpms.remirepo.net/enterprise/remi-release-8.rpm
dnf config-manager --set-enabled remi
# https://nginx.org/ru/linux_packages.html#RHEL-CentOS
dnf install https://dev.mysql.com/get/mysql80-community-release-el8-1.noarch.rpm
dnf install https://repo.manticoresearch.com/manticore-repo.noarch.rpm
dnf upgrade
```
- Установить MySQL 8.0
```bash
dnf remove @mysql
dnf module reset mysql
dnf module disable mysql
dnf config-manager --set-enabled mysql80-community
dnf install mysql-community-server
systemctl enable --now mysqld.service
grep 'A temporary password' /var/log/mysqld.log |tail -1
mysql_secure_installation
```
- Установить Nginx
```bash
dnf config-manager --set-disabled nginx-mainline
dnf config-manager --set-enabled nginx-stable
dnf install nginx
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --reload
systemctl enable nginx
```
- Установить php 8.0
```bash
dnf module reset php
dnf module install php:remi-8.0
dnf install php-fpm php-cli php-gd php-intl php-json php-mbstring php-mysqlnd php-opcache php-pdo php-pecl-apcu php-pecl-zip php-process php-xml php-sodium
systemctl enable php-fpm
```
- Установить cron
```bash
dnf install crontabs
systemctl enable crond
```
- Установить manticore
```bash
dnf config-manager --set-enabled manticore
dnf install manticore
### fix the manticore
echo 'export MYSQL_LIB=/usr/lib64/mysql/libmysqlclient.so.21' > /etc/profile.d/mysql-manticore.csh
systemctl enable manticore
```


#### Дополнительные настройки
```bash
timedatectl set-timezone Europe/Moscow
dnf install git htop mc tar unzip
# /etc/ssh/sshd_config change Port to 2222
firewall-cmd --permanent --remove-service=ssh
firewall-cmd --permanent --add-port=2222/tcp
firewall-cmd --reload
systemctl restart sshd
```


##### Установка p7zip
```bash
dnf install make gcc gcc-c++
cd /opt
mkdir p7zip_17.04_sources
mkdir p7zip_17.04_build
cd /opt/p7zip_17.04_sources
curl -O -L https://github.com/jinfeihan57/p7zip/archive/v17.04.tar.gz
tar xzvf v17.04.tar.gz
cd p7zip-17.04
make all3
# изменить в файле install.sh переменную DEST_HOME на /opt/p7zip_17.04_build
./install.sh
```
Проверить список поддерживаемых форматов можно так `/opt/p7zip_17.04_build/bin/7z i` или `/opt/p7zip_17.04_build/bin/7za i` 


### Установка FFmpeg:
Базовая информация: [https://trac.ffmpeg.org/wiki/CompilationGuide/Centos](https://trac.ffmpeg.org/wiki/CompilationGuide/Centos).
В конце проверить что на всех директориях выше и самих бинарниках есть права на выполнение.
```bash
dnf install autoconf automake bzip2 bzip2-devel cmake freetype-devel gcc gcc-c++ libtool make nasm yasm pkgconfig zlib-devel

mkdir /opt/ffmpeg_2021-05-15_build
mkdir /opt/ffmpeg_2021-05-15_source
build_directory="/opt/ffmpeg_2021-05-15_build"
source_directory="/opt/ffmpeg_2021-05-15_source"
PATH="$build_directory/bin:$PATH"

cd $source_directory
git clone --depth 1 --branch stable https://code.videolan.org/videolan/x264.git
cd x264
PKG_CONFIG_PATH="$build_directory/lib/pkgconfig" ./configure --prefix="$build_directory" --bindir="$build_directory/bin" --enable-static
make
make install
make distclean

cd $source_directory
git clone --depth 1 --branch stable https://bitbucket.org/multicoreware/x265_git.git
cd x265_git
cd build/linux
cmake -G "Unix Makefiles" -DCMAKE_INSTALL_PREFIX="$build_directory" -DENABLE_SHARED=OFF ../../source
make
make install
make clean

####
# create by hand the fucking pc file
# /opt/ffmpeg_2021-05-15_build/lib/pkgconfig/x265.pc
#
# prefix=/opt/ffmpeg_2021-05-15_build
# exec_prefix=${prefix}
# libdir=${exec_prefix}/lib
# includedir=${prefix}/include
#
# Name: x265
# Description: H.265/HEVC video encoder
# Version: 3.5
# Libs: -L${libdir} -lx265
# Libs.private: -lstdc++ -lm -lrt -ldl
# Cflags: -I${includedir}
###


cd $source_directory
curl -O -L https://archive.mozilla.org/pub/opus/opus-1.3.1.tar.gz
tar xzvf opus-1.3.1.tar.gz
cd opus-1.3.1
./configure --prefix="$build_directory" --disable-shared
make
make install
make distclean

cd $source_directory
curl -O -L https://ftp.osuosl.org/pub/xiph/releases/ogg/libogg-1.3.4.tar.gz
tar xzvf libogg-1.3.4.tar.gz
cd libogg-1.3.4
./configure --prefix="$build_directory" --disable-shared
make
make install
make distclean

cd $source_directory
curl -O -L https://ftp.osuosl.org/pub/xiph/releases/vorbis/libvorbis-1.3.7.tar.gz
tar xzvf libvorbis-1.3.7.tar.gz
cd libvorbis-1.3.7
LDFLAGS="-L$build_directory/lib" CPPFLAGS="-I$build_directory/include" ./configure --prefix="$build_directory" --with-ogg="$build_directory" --disable-shared
make
make install
make distclean

cd $source_directory
curl -O -L https://ftp.osuosl.org/pub/xiph/releases/theora/libtheora-1.1.1.tar.gz
tar xzvf libtheora-1.1.1.tar.gz
cd libtheora-1.1.1
./configure --prefix="$build_directory" --with-ogg="$build_directory" --disable-examples --disable-shared --disable-sdltest --disable-vorbistest
make
make install
make distclean

cd $source_directory
curl -O -L https://downloads.sourceforge.net/project/lame/lame/3.100/lame-3.100.tar.gz
tar xzvf lame-3.100.tar.gz
cd lame-3.100
./configure --prefix="$build_directory" --bindir="$build_directory/bin" --disable-shared --enable-nasm
make
make install
make distclean

cd $source_directory
git clone https://chromium.googlesource.com/webm/libvpx.git 
cd libvpx
git checkout tags/v1.10.0
./configure --prefix="$build_directory" --disable-examples --disable-unit-tests --enable-vp9-highbitdepth --as=yasm
make
make install
make clean

cd $source_directory
curl -O -L https://downloads.sourceforge.net/project/opencore-amr/opencore-amr/opencore-amr-0.1.5.tar.gz
tar -xzvf opencore-amr-0.1.5.tar.gz
cd opencore-amr-0.1.5
autoreconf -fiv
./configure --prefix="$build_directory" --disable-shared
make
make install
ldconfig
make clean
make distclean

cd $source_directory
git clone --depth 1 --branch release/4.4 https://github.com/FFmpeg/FFmpeg.git
cd FFmpeg
PKG_CONFIG_PATH="$build_directory/lib/pkgconfig" ./configure \
    --prefix="$build_directory" \
    --extra-cflags="-I$build_directory/include" \
    --extra-ldflags="-L$build_directory/lib" \
    --extra-libs=-lpthread \
    --extra-libs=-lm \
    --bindir="$build_directory/bin" \
    --pkg-config-flags="--static" \
    --enable-gpl \
    --enable-nonfree \
    --enable-libfreetype \
    --enable-libmp3lame \
    --enable-libopus \
    --enable-libvorbis \
    --enable-libvpx \
    --enable-libx264 \
    --enable-libx265 \
    --enable-libopencore-amrwb \
    --enable-libopencore-amrnb \
    --enable-libtheora \
    --enable-version3
make
make install
make distclean
hash -r
```


### Установка сайта
```bash
cd /var/www
curl -L -o composer.phar https://github.com/composer/composer/releases/download/2.1.6/composer.phar
chmod 755 composer.phar
curl -L -o GeoLite2-Country.mmdb https://github.com/P3TERX/GeoLite.mmdb/raw/download/GeoLite2-Country.mmdb

git clone https://github.com/Gemorroj/wapinet.git
cd wapinet
cp .env.dist .env
../composer.phar install --no-dev --optimize-autoloader --apcu-autoloader
rm -rf ./var/cache/*
rm -rf ./var/log/*
rm -rf ./var/tmp/*
service php-fpm restart
```


### Установка прав доступа на запись:
`var/log`  
`var/tmp`  
`var/cache`  
`public/media/cache/resolve/thumbnail/static`  
`public/media/cache/thumbnail/static`  
`public/static/file`


### Установка cron заданий:
##### Каждый день в 1 час ночи от пользователя php-fpm
`php /var/www/wapinet/bin/console app:tmp-clear "1 day"`    
`php /var/www/wapinet/bin/console app:tags-clear`
##### Каждые пол часа от пользователя php-fpm
`php /var/www/wapinet/bin/console app:subscriber-send`
##### Каждый день в 2 часа ночи от пользователя manticore
`indexer --rotate --all`


### СУБД
```bash
mysql -u root -p
CREATE USER 'wapinet'@'localhost' IDENTIFIED BY 'password';
GRANT ALL ON wapinet.* TO 'wapinet'@'localhost';
GRANT SELECT ON forum.* TO 'wapinet'@'localhost';
quit
mysql -u wapinet -p wapinet < wapinet.sql
```


### SSL сертификаты
##### Установка
```bash
dnf install socat
curl https://get.acme.sh | sh -s email=wapinet@mail.ru
systemctl stop nginx
acme.sh --issue --standalone -d wapinet.ru
systemctl start nginx

# remove all jobs
crontab -r
```
##### Обновление
```bash
acme.sh --upgrade
service nginx stop
acme.sh --renew-all --force
service nginx start
```


### Конфиг nginx:
```nginx
server {
    location ~ /\.well-known\/acme-challenge {
        allow all;
    }
    location ~ /\. {
        deny all;
    }

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_certificate /path_to_fullchain.pem;
    ssl_certificate_key /path_to_key.pem;
    ssl_trusted_certificate /path_to_chain.pem;

    charset utf-8;
    listen 443 ssl http2;

    server_name wapinet.ru www.wapinet.ru;
    root /var/www/wapinet/public;

    error_log /var/log/nginx/wapinet.error.log;
    access_log /var/log/nginx/wapinet.access.log;

    # todo: Content-Security-Policy
    add_header Strict-Transport-Security "max-age=31536000";
    add_header X-Frame-Options "DENY";

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
        fastcgi_pass unix:/run/php-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
       # When you are using symlinks to link the document root to the
       # current version of your application, you should pass the real
       # application path instead of the path to the symlink to PHP
       # FPM.
       # Otherwise, PHP's OPcache may not properly detect changes to
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
}
```

### TODO:
- ! проверка файлов на virustotal (возможно автоматическая) в файлообменнике
- !!! заменить свою curl прослойку на https://github.com/symfony/http-client
- !!! Переделать интерфейс на vue/react/angular (огромная задача)
- переделать редактор аудиотегов на https://github.com/duncan3dc/meta-audio/issues/3 (когда будут картинки)
- ! Сделать возможность в обфускаторе загружать файлы
- !! Актуализировать мобильные коды. Найти новые для разных андроидов.
- Данные о видео на youtube и др. https://github.com/essence/essence
- Оболочка над nmap https://github.com/willdurand/nmap (работает медленно и выдает мало информации)
- Проверка на спамеров http://www.stopforumspam.com/usage (https://github.com/Gemorroj/StopSpam)
