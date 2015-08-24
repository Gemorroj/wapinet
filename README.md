# Сайт wapinet.ru

### Установка крон заданий:
`php /var/www/wapinet.ru/app/console --env=prod wapinet:tmp:clear "1 day"`  
`php /var/www/wapinet.ru/app/console --env=prod wapinet:user:subscriber`  
`indexer --rotate --all`  


### Установка прав доступа на запись:
`app/logs`  
`app/tmp`  
`app/cache`  
`web/media/cache/resolve/thumbnail/static`  
`web/media/cache/thumbnail/static/avatar`  
`web/media/cache/thumbnail/static/file`  
`web/static/avatar`  
`web/static/file`  


### Установка FFmpeg:
Делаем все как указано по ссылке [https://trac.ffmpeg.org/wiki/CompilationGuide/Centos](https://trac.ffmpeg.org/wiki/CompilationGuide/Centos).  
Дополнительно ставим `theora`, `libfaac`, `amr`. Не забываем указать в конфиге `--prefix="$build_directory"`, а для `theora` еще и `--with-ogg="$HOME/ffmpeg_build" --disable-shared`.  
В конце проверить что на всех директориях выше и самих бинарниках есть права на выполнение.

    build_directory="/root/ffmpeg_23_08_2015_build"
    sources_directory="/root/ffmpeg_23_08_2015_sources"
    
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
    ./configure --prefix="$build_directory" --bindir="$build_directory/bin" --enable-static
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
    git clone --depth 1 git://source.ffmpeg.org/ffmpeg
    cd ffmpeg
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


### TODO:
- Сделать возможность в переводчике и обфускаторе загружать файлы
- Актуализировать мобильные коды. Найти новые для разных андроидов.
- заменить апи сфинкса https://github.com/FoolCode/SphinxQL-Query-Builder
- Добавить в профиль ссылку на vk пользователя. Доп данные тут: https://vk.com/dev/users.get получить данные можно так https://api.vk.com/method/users.get?user_ids=gemorroj (готовый пакет для работы с апи vk https://github.com/SocialConnect/vk)
- Данные о видео https://github.com/essence/essence
- Оболочка над nmap https://github.com/willdurand/nmap


### BUG:
[2014-12-03 23:32:45] request.INFO: Matched route "icq_registration_pic" (parameters: "_controller": "Wapinet\Bundle\Controller\IcqController::registrationPicAction", "_format": "png", "gnm_img": "74AA504ABC19FF0318CFCE2DADDB0965CC79F51CDB17EA621D38573F559D3F130693D136627761CFBEABD4832F6B8BEAB", "_route": "icq_registration_pic") [] []
[2014-12-03 23:32:45] security.INFO: Populated SecurityContext with an anonymous Token [] []
[2014-12-03 23:32:45] request.CRITICAL: Uncaught PHP Exception RuntimeException: "Не удалось получить данные (HTTP код: 410)" at /var/www/wapinet.ru/src/Wapinet/Bundle/Controller/IcqController.php line 133 {"exception":"[object] (RuntimeException: Не удалось получить данные (HTTP код: 410) at /var/www/wapinet.ru/src/Wapinet/Bundle/Controller/IcqController.php:133)"} []
