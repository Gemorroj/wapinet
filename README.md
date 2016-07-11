# Сайт wapinet.ru

### Установка крон заданий:
`php /var/www/wapinet.ru/app/console --env=prod wapinet:tmp:clear "1 day"`  
`php /var/www/wapinet.ru/app/console --env=prod wapinet:tags:clear`  
`php /var/www/wapinet.ru/app/console --env=prod wapinet:user:subscriber`  
`indexer --rotate --all`  

### Установка прав доступа на запись:
`app/logs`  
`app/tmp`  
`app/cache`  
`web/media/cache/resolve/thumbnail/static`  
`web/media/cache/thumbnail/static`  
`web/static/file`  

### Установка p7zip:
```bash
    yum install p7zip
```

#### Если версия в репозитории слишком старая, то ставим из исходников
```bash
cd /root/p7zip_15.14_sources
curl -L  http://downloads.sourceforge.net/project/p7zip/p7zip/15.14/p7zip_15.14_src_all.tar.bz2 > p7zip_15.14_src_all.tar.bz2
tar xjvf p7zip_15.14_src_all.tar.bz2
cd p7zip_15.14
# изменить в файле install.sh переменную DEST_HOME на /root/p7zip_15.14_build
make all3
./install.sh
```

### Установка FFmpeg:
Делаем все как указано по ссылке [https://trac.ffmpeg.org/wiki/CompilationGuide/Centos](https://trac.ffmpeg.org/wiki/CompilationGuide/Centos).  
Дополнительно ставим `theora`, `amr`. Не забываем указать в конфиге `--prefix="$build_directory"`, а для `theora` еще и `--with-ogg="$HOME/ffmpeg_build" --disable-shared`.
В конце проверить что на всех директориях выше и самих бинарниках есть права на выполнение.
```bash
build_directory="/root/ffmpeg_17_01_2016_build"
sources_directory="/root/ffmpeg_17_01_2016_sources"
PATH="$build_directory/bin:$PATH"

cd $sources_directory
curl -O http://www.nasm.us/pub/nasm/releasebuilds/2.12.01/nasm-2.12.01.tar.gz
tar xzvf nasm-2.12.01.tar.gz
cd nasm-2.12.01
autoreconf -fiv
./configure --prefix="$build_directory" --bindir="$build_directory/bin"
make
make install
make distclean

cd $sources_directory
git clone --depth 1 git://github.com/yasm/yasm.git
cd yasm
autoreconf -fiv
./configure --prefix="$build_directory" --bindir="$build_directory/bin"
make
make install
make distclean

cd $sources_directory
git clone --depth 1 git://git.videolan.org/x264
cd x264
PKG_CONFIG_PATH="$build_directory/lib/pkgconfig" ./configure --prefix="$build_directory" --bindir="$build_directory/bin" --enable-static
make
make install
make distclean

cd $sources_directory
hg clone https://bitbucket.org/multicoreware/x265 -r stable
cd x265/build/linux
cmake -G "Unix Makefiles" -DCMAKE_INSTALL_PREFIX="$build_directory" -DENABLE_SHARED=OFF ../../source
make
make install
make clean

cd $sources_directory
git clone --depth 1 git://github.com/mstorsjo/fdk-aac.git
cd fdk-aac
./autogen.sh
./configure --prefix="$build_directory" --disable-shared
make
make install
ldconfig
make distclean

cd $sources_directory
git clone --depth 1 git://github.com/rbrito/lame.git
cd lame
./configure --prefix="$build_directory" --bindir="$build_directory/bin" --disable-shared --enable-nasm
make
make install
make distclean

cd $sources_directory
git clone git://git.opus-codec.org/opus.git
cd opus
autoreconf -fiv
./configure --prefix="$build_directory" --disable-shared
make
make install
make distclean

cd $sources_directory
curl -O http://downloads.xiph.org/releases/ogg/libogg-1.3.2.tar.gz
tar xzvf libogg-1.3.2.tar.gz
cd libogg-1.3.2
./configure --prefix="$build_directory" --disable-shared
make
make install
make distclean

cd $sources_directory
curl -O http://downloads.xiph.org/releases/vorbis/libvorbis-1.3.5.tar.gz
tar xzvf libvorbis-1.3.5.tar.gz
cd libvorbis-1.3.5
LDFLAGS="-L$build_directory/lib" CPPFLAGS="-I$build_directory/include" ./configure --prefix="$build_directory" --with-ogg="$build_directory" --disable-shared
make
make install
make distclean

cd $sources_directory
curl -O http://downloads.xiph.org/releases/theora/libtheora-1.1.1.tar.gz
tar xzvf libtheora-1.1.1.tar.gz
cd libtheora-1.1.1
./configure --prefix="$build_directory" --with-ogg="$build_directory" --disable-examples --disable-shared --disable-sdltest --disable-vorbistest
make
make install
make distclean

cd $sources_directory
git clone --depth 1 https://chromium.googlesource.com/webm/libvpx.git
cd libvpx
./configure --prefix="$build_directory" --disable-examples
make
make install
make clean

cd $sources_directory
git clone --depth 1 git://github.com/BelledonneCommunications/opencore-amr.git
cd opencore-amr
autoreconf -fiv
./configure --prefix="$build_directory" --disable-shared
make
make install
ldconfig
make clean
make distclean

cd $sources_directory
git clone --depth 1 -b release/3.0 https://github.com/FFmpeg/FFmpeg.git
cd FFmpeg
PKG_CONFIG_PATH="$build_directory/lib/pkgconfig" ./configure --prefix="$build_directory" --extra-cflags="-I$build_directory/include" --extra-ldflags="-L$build_directory/lib" --bindir="$build_directory/bin" --pkg-config-flags="--static" --enable-gpl --enable-nonfree --enable-libfdk-aac --enable-libfreetype --enable-libmp3lame --enable-libopus --enable-libvorbis --enable-libvpx --enable-libx264 --enable-libx265 --enable-libopencore-amrwb --enable-libopencore-amrnb --enable-libtheora --enable-version3
make
make install
make distclean
hash -r
```

### Конфйиг nginx:
```nginx
server {
    #location ~ /\. {
    #    deny all;
    #}

    charset utf-8;
    listen 80;

    server_name wapinet.ru www.wapinet.ru;
    root /var/www/wapinet/web;

    error_log /var/log/nginx/wapinet.error.log;
    access_log /var/log/nginx/wapinet.access.log;


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
    location /media/ {
        access_log off;
        expires 30d;
    }
    location /bundles/ {
        access_log off;
        expires 30d;
    }
    location /static/ {
        # Скачивание всех файлов (в т.ч. и txt, html и проч. в обменнике), чтобы потенциальный html/js код не выполнился в браузере
        add_header Content-Disposition "attachment";
        access_log off;
        expires 30d;
    }
    ###


    location / {
        # try to serve file directly, fallback to app.php
        try_files $uri /app.php$is_args$args;
    }

    location ~ ^/app\.php(/|$) {
        fastcgi_pass phpfcgi;
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

        fastcgi_param  HTTPS off;

        # Prevents URIs that include the front controller. This will 404:
        # http://domain.tld/app.php/some-path
        # Remove the internal directive to allow URIs like this
        internal;
   }

    location ~ \.php$ {
        return 404;
    }
}
```

### BUG:
 - ...

### TODO:
- Заменить апи сфинкса https://github.com/FoolCode/SphinxQL-Query-Builder
- Сделать возможность в переводчике и обфускаторе загружать файлы
- Актуализировать мобильные коды. Найти новые для разных андроидов.
- Данные о видео на youtube и др. https://github.com/essence/essence
- Оболочка над nmap https://github.com/willdurand/nmap (работает медленно и выдает мало информации)
- Сделать лайки или дизлайки в файлообменнике. По аналогии с комментариями.
