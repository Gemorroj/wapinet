"use strict";

var FileLoader = {
    readFile: function (file, callback) {
        if (FileReader) {
            var fileReader = new FileReader();
            fileReader.onload = callback;
            fileReader.readAsDataURL(file);
            return fileReader;
        }
        return false;
    },
    preview: function (e, file, previewElement) {
        var container = document.createElement('p');
        container.className = 'container-preview';

        if (file.type.match('^image/.+')) {
            var img = new Image();
            img.src = e.target.result;
            img.className = 'image-preview';
            container.appendChild(img);
        }

        var size = document.createTextNode(' ' + Helper.sizeFormat(e.total));
        container.appendChild(size);

        $(previewElement).parent().prepend(container);
    },
    previewCleaner: function (previewElement) {
        $(previewElement).parent().find('p.container-preview').remove();
    },
    uploadFile: function (form) {
        var $uploadLoader = $('<span></span>');

        $.mobile.loading("show", {
            text: "Загрузка файла... ",
            textVisible: true
        });

        if (FormData === undefined || XMLHttpRequest === undefined) {
            return true;
        }

        var xhr = new XMLHttpRequest();
        if (xhr.upload === undefined) {
            xhr.close();
            return true;
        }

        var formData = new FormData(form);
        $uploadLoader.appendTo('div.ui-loader h1');

        xhr.upload.addEventListener("progress", function (e) {
            if (e.lengthComputable) {
                var percentComplete = Math.round(e.loaded * 100 / e.total);
                $uploadLoader.text(percentComplete.toString() + '%');
            } else {
                $uploadLoader.text('Неизвестно.');
            }
        }, false);
        xhr.addEventListener("load", function (e) {
            var responseJson = JSON.parse(e.target.responseText);
            if (e.target.status === 200) {
                window.location.assign(responseJson.url);
            } else {
                $uploadLoader.text('Ошибка: ' + responseJson.errors.join(". "));
                setTimeout(function () {
                    $.mobile.loading("hide");
                }, 10000);
            }
        }, false);
        xhr.addEventListener("error", function () {
            $uploadLoader.text('Загрузка прервана.');
        }, false);
        xhr.addEventListener("abort", function () {
            $uploadLoader.text('Загрузка остановлена.');
        }, false);

        xhr.open(form.getAttribute('method'), form.getAttribute('action'));
        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        xhr.send(formData);

        return false;
    },
    saveAsHandler: function (link, text, fileName) {
        var $link = $(link);
        $link.hide();
        if (Blob) {
            $link.show();
            $link.click(function () {
                var blob = new Blob(
                    [$(text).val()],
                    {"type": "text/plain;charset=utf-8"}
                );
                // saveAs реализована в FileSaver
                saveAs(blob, fileName || "file.txt");
            });
        }
    }
};
var Helper = {
    sizeFormat: function (fileSizeInBytes) {
        var i = -1;
        var byteUnits = [' kB', ' MB', ' GB', ' TB', 'PB', 'EB', 'ZB', 'YB'];
        do {
            fileSizeInBytes = fileSizeInBytes / 1024;
            i++;
        } while (fileSizeInBytes > 1024);

        return Math.max(fileSizeInBytes, 0.1).toFixed(1) + byteUnits[i];
    },
    captchaReload: function (path, id) {
        var img = document.getElementById(id);
        img.src = path + '?n=' + (new Date()).getTime();
    },
    escapeHtml: function (str) {
        return str.toString().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    },
    escapeUrl: function (str) {
        return window.encodeURI(str.toString());
    }
};
var ImagePreview = {
    loadComments: function () {
        // превью картинок в комментариях и гостевой
        $("div.long-description img.bb").each(function (i) {
            var $image = $(this);

            var src = Helper.escapeHtml(Helper.escapeUrl($image.attr('src') || ''));
            var alt = Helper.escapeHtml($image.attr('alt') || '');

            $image.replaceWith(
                '<a href="#popup-' + i + '" data-rel="popup" data-position-to="window" data-transition="fade">' +
                '<img src="' + src + '" alt="" class="image-preview" />' +
                '</a>' +
                '<div data-role="popup" id="popup-' + i + '" data-overlay-theme="b" data-theme="b" data-corners="false">' +
                '<a href="#" data-rel="back" class="ui-btn ui-corner-all ui-shadow ui-btn-a ui-icon-delete ui-btn-icon-notext ui-btn-right">Закрыть</a>' +
                '<img src="' + src + '" alt="' + alt + '" style="width: 100%;" />' +
                '</div>'
            );
        });
    }
};
var Autocomplete = {
    text: function (source, input, suggestions) {
        input = input || '#autocomplete';
        suggestions = suggestions || '#suggestions';
        $(input).autocomplete({
            link: '#',
            target: $(suggestions),
            source: source,
            loadingHtml: '',
            icon: 'tag',
            callback: function (e) {
                var $input = $(input);

                var arrText = $input.val().split(',');

                arrText.pop();
                arrText.push($(e.currentTarget).text());

                var text = arrText.join(',');

                $input.val(text);
                $input.autocomplete('clear');
            },
            minLength: 2,
            interval: 1
        });
    },
    link: function (link, source, input, suggestions) {
        input = input || '#autocomplete';
        suggestions = suggestions || '#suggestions';
        $(input).autocomplete({
            link: link,
            target: $(suggestions),
            source: source,
            loadingHtml: '<li data-icon="none"><a href="#">Поиск...</a></li>',
            minLength: 2,
            interval: 1
        });
    }
};


$(document).on("pagebeforeshow", "#page", function () {
    $.mobile.ajaxEnabled = false;

    // подгрузка картинок в попапах
    $("a[href^='#image-']").on("click", function () {
        $.mobile.loading("show");

        var popup = $(this.getAttribute('href'));
        var img = popup.find("img");

        var src = img.attr('src');
        var srcData = img.data('src');

        if (src === srcData) {
            popup.popup("open");
            $.mobile.loading("hide");
        } else {
            img.load(function () {
                popup.popup("open");
                $.mobile.loading("hide");
            });

            img.attr('src', srcData);
        }

        return false;
    });


    // предпросмотр картинок в загружаемых файлах
    $('input[type="file"]').change(function (e) {
        var fileElement = e.target;
        // только если поддерживается
        if (fileElement.files) {
            var file = fileElement.files[0];
            FileLoader.previewCleaner(fileElement);
            FileLoader.readFile(file, function (e) {
                FileLoader.preview(e, file, fileElement);
            });

        }
    });

    // выключение взаимозаменяющих полей в url_file
    var fileUrl = $('fieldset.file-url');
    fileUrl.find('input[type="file"]').change(function (e) {
        var state = 'enable';
        if (e.target.value) {
            state = 'disable';
        }
        fileUrl.find('input[type="url"]').textinput(state);
    });
    fileUrl.find('input[type="url"]').change(function (e) {
        var state = 'enable';
        if (e.target.value) {
            state = 'disable';
        }
        fileUrl.find('input[type="file"]').textinput(state);
    });

    // обновление капчи
    $('a.captcha-reload').click(function () {
        var $this = $(this);
        Helper.captchaReload($this.data('path'), $this.data('id'));
    });

    // превью картинок в комментариях
    ImagePreview.loadComments();
    $("div.fos_comment_thread").enhanceWithin();
});

$(document).ajaxStart(function () {
    $.mobile.loading('show');
}).ajaxSuccess(function () {
    $.mobile.loading('hide');
    ImagePreview.loadComments();
    $("#page").enhanceWithin();
}).ajaxError(function () {
    $.mobile.loading('hide');
    $.mobile.loading("show", {
        text: "Ошибка",
        textVisible: true,
        textonly: true
    });
    setTimeout(function () {
        $.mobile.loading("hide");
    }, 10000);
    $("#page").enhanceWithin();
});
