var FileLoader = {
    readFile: function (file, callback) {
        if (window.FileReader) {
            var fileReader = new window.FileReader();
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
    }
};


$(document).on("pagebeforeshow", "#page", function () {
    $.mobile.ajaxEnabled = false;
    $.mobile.ajaxFormsEnabled = false;

    // предпросмотр картинок в загружаемых файлах
    $('input[type="file"]').change(function (e) {
        var fileElement = e.target;
        // только если поддерживается
        if (fileElement.files) {
            FileLoader.previewCleaner(fileElement);
            $.each(fileElement.files, function (i, file) {
                FileLoader.readFile(file, function (e) {
                    FileLoader.preview(e, file, fileElement);
                });
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
});

$(document).ajaxStart(function () {
    $.mobile.loading('show');
}).ajaxSuccess(function () {
    $.mobile.loading('hide');
    $("#page").trigger("pagecreate");
}).ajaxError(function () {
    $.mobile.loading('hide');
    $.mobile.loading("show", {
        text: "Ошибка",
        textVisible: true,
        textonly: true
    });
    setTimeout(function () {
        $.mobile.loading("hide");
    }, 3000);
});
