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
    }
};


$(document)/*.bind("mobileinit", function () {
    $.mobile.ajaxEnabled = false;
    $.mobile.ajaxFormsEnabled = false;
})*/.ready(function () {
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
});

$.ajaxSetup({
    "complete": function () {
        $("#page").trigger("pagecreate");
    },
    "error": function () {
        $.mobile.loading("show", {
            text: "Ошибка",
            textVisible: true
        });
        setTimeout(function () {
            $.mobile.loading("hide");
        }, 3000);
    }
});
