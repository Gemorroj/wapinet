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

Отсюда берем пример ннедостающих библиотек (libfaac, amr) не забываем какзатьв  конфиге --prefix="$HOME/ffmpeg_build" --with-ogg="$HOME/ffmpeg_build" --disable-shared
http://www.alduccino.com/installing-ffmppeg-flvtool2-and-yamdi-on-centos-6


Конфиг ffmpeg
`./configure --prefix="$HOME/ffmpeg_build" --extra-cflags="-I$HOME/ffmpeg_build/include" --extra-ldflags="-L$HOME/ffmpeg_build/lib" --bindir="$HOME/bin" --extra-libs=-ldl --enable-gpl --enable-nonfree --enable-libfdk_aac --enable-libmp3lame --enable-libopus --enable-libvorbis --enable-libvpx --enable-libx264 --enable-libfaac --enable-libvorbis --enable-libopencore-amrwb --enable-libopencore-amrnb  --enable-libtheora --enable-version3`


В конце проверить что на всех директориях выше и самих бинарниках есть права на выполнение.



TODO:
В гостевой для анонимусов сделать отображение страны по geoip2
Сделать лог изменений сайта (hg log -l 10)

