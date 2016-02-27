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
    cd /root/p7zip_15.09_sources
    curl -L  http://downloads.sourceforge.net/project/p7zip/p7zip/15.09/p7zip_15.09_src_all.tar.bz2 > p7zip_15.09_src_all.tar.bz2
    tar xjvf p7zip_15.09_src_all.tar.bz2
    cd p7zip_15.09
изменить в файле install.sh переменную DEST_HOME на /root/p7zip_15.09_build
    make all3
    ./install.sh


### Установка FFmpeg:
Делаем все как указано по ссылке [https://trac.ffmpeg.org/wiki/CompilationGuide/Centos](https://trac.ffmpeg.org/wiki/CompilationGuide/Centos).  
Дополнительно ставим `theora`, `libfaac`, `amr`. Не забываем указать в конфиге `--prefix="$build_directory"`, а для `theora` еще и `--with-ogg="$HOME/ffmpeg_build" --disable-shared`.  
В конце проверить что на всех директориях выше и самих бинарниках есть права на выполнение.

    build_directory="/root/ffmpeg_17_01_2016_build"
    sources_directory="/root/ffmpeg_17_01_2016_sources"
    PATH="$build_directory/bin:$PATH"
    
    cd $sources_directory
    curl -O http://www.nasm.us/pub/nasm/releasebuilds/2.11.08/nasm-2.11.08.tar.gz
    tar xzvf nasm-2.11.08.tar.gz
    cd nasm-2.11.08
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
    hg clone https://bitbucket.org/multicoreware/x265
    cd x265/build/linux
    cmake -G "Unix Makefiles" -DCMAKE_INSTALL_PREFIX="$build_directory" -DENABLE_SHARED:bool=off ../../source
    make
    make install
    
    cd $sources_directory
    git clone --depth 1 git://git.code.sf.net/p/opencore-amr/fdk-aac
    cd fdk-aac
    autoreconf -fiv
    ./configure --prefix="$build_directory" --disable-shared
    make
    make install
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
    git clone --depth 1 git://github.com/Arcen/faac.git
    cd faac
    ./bootstrap
    ./configure --prefix="$build_directory" --disable-shared
    make
    make install
    ldconfig
    make clean
    make distclean
    
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
    git clone --depth 1 -b release/2.8 https://github.com/FFmpeg/FFmpeg.git
    cd FFmpeg
    PKG_CONFIG_PATH="$build_directory/lib/pkgconfig" ./configure --prefix="$build_directory" --extra-cflags="-I$build_directory/include" --extra-ldflags="-L$build_directory/lib" --bindir="$build_directory/bin" --pkg-config-flags="--static" --enable-gpl --enable-nonfree --enable-libfdk-aac --enable-libfreetype --enable-libmp3lame --enable-libopus --enable-libvorbis --enable-libvpx --enable-libx264 --enable-libx265 --enable-libfaac --enable-libopencore-amrwb --enable-libopencore-amrnb --enable-libtheora --enable-version3
    make
    make install
    make distclean
    hash -r



### Конфйиг nginx:
    location ~ /\. {
        deny all;
    }

    charset utf-8;
    listen 80;

    server_name wapinet.ru www.wapinet.ru;
    root /var/www/wapinet.ru/web;

    error_log /var/log/nginx/wapinet.ru.error.log;
    access_log /var/log/nginx/wapinet.ru.access.log;

    # strip app.php/ prefix if it is present
    rewrite ^/app\.php/?(.*)$ /$1 permanent;

    location / {
        index app.php;
        try_files $uri @rewriteapp;
    }

    location @rewriteapp {
        rewrite ^(.*)$ /app.php/$1 last;
    }

    # pass the PHP scripts to FastCGI server from upstream phpfcgi
    location ~ ^/(app|app_dev|config)\.php(/|$) {
        fastcgi_pass phpfcgi;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param  HTTPS off;
    }

    location /static/ {
        add_header Content-Disposition "attachment";
    }

    location /(bundles|media|static|favicon\.ico|robots\.txt|apple-touch-icon\.png) {
        access_log off;
        expires 30d;

        try_files $uri @rewriteapp;
    }


### BUG:
 - ...

### TODO:
- Заменить апи сфинкса https://github.com/FoolCode/SphinxQL-Query-Builder
- Сделать возможность в переводчике и обфускаторе загружать файлы
- Актуализировать мобильные коды. Найти новые для разных андроидов.
- Данные о видео на youtube и др. https://github.com/essence/essence
- Оболочка над nmap https://github.com/willdurand/nmap (работает медленно и выдает мало информации)
- Сделать лайки или дизлайки в файлообменнике. По аналогии с комментариями.
