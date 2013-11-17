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
        container.className = 'container_preview';

        if (file.type.match('^image/.+')) {
            var img = new Image();
            img.src = e.target.result;
            img.className = 'image_preview';
            container.appendChild(img);
        }

        var size = document.createTextNode(' ' + Helper.sizeFormat(e.total));
        container.appendChild(size);

        $(previewElement).parent().prepend(container);
    },
    previewCleaner: function (previewElement) {
        $(previewElement).parent().find('p.container_preview').remove();
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
        $.each(fileElement.files, function (i, file) {
            FileLoader.previewCleaner(fileElement);
            FileLoader.readFile(file, function (e) {
                FileLoader.preview(e, file, fileElement);
            });
        });
    });

    // выключение взаимозаменяющих полей в url_file
    var fileUrl = $('fieldset.file_url');
    fileUrl.find('input[type="file"]').change(function (e) {
        if (e.target.files[0]) {
            fileUrl.find('input[type="url"]').textinput("disable");
        }
    });
    fileUrl.find('input[type="url"]').change(function (e) {
        if (e.target.value) {
            fileUrl.find('input[type="file"]').textinput("disable");
        }
    });
});

$.ajaxSetup({
    "complete": function () {
        $("#page").trigger("pagecreate");
    }
});
