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


Установка FFmpeg:

Делаем все как указано, из дополнений ставим theora
https://trac.ffmpeg.org/wiki/CompilationGuide/Centos

Отсюда берем пример недостающих библиотек (libfaac, amr) не забываем указать в конфиге --prefix="$HOME/ffmpeg_build" --with-ogg="$HOME/ffmpeg_build" --disable-shared
http://www.alduccino.com/installing-ffmppeg-flvtool2-and-yamdi-on-centos-6


Конфиг ffmpeg
`./configure --prefix="$HOME/ffmpeg_build" --extra-cflags="-I$HOME/ffmpeg_build/include" --extra-ldflags="-L$HOME/ffmpeg_build/lib" --bindir="$HOME/bin" --extra-libs=-ldl --enable-gpl --enable-nonfree --enable-libfdk_aac --enable-libmp3lame --enable-libopus --enable-libvorbis --enable-libvpx --enable-libx264 --enable-libfaac --enable-libvorbis --enable-libopencore-amrwb --enable-libopencore-amrnb  --enable-libtheora --enable-version3`


В конце проверить что на всех директориях выше и самих бинарниках есть права на выполнение.



Конфйиг nginx:
```
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
```


TODO:
В гостевой для анонимусов сделать отображение страны по geoip2
Сделать возможность в переводчике и обфускаторе загружать файлы
