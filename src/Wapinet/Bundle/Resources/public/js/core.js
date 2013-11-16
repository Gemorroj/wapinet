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
    imagePreview: function (e, previewElement) {
        var img = new Image();
        img.src = e.target.result;
        img.className = 'image_preview';

        $(previewElement).parent().prepend(img);
    },
    imagePreviewCleaner: function (previewElement) {
        $(previewElement).parent().find('img.image_preview').remove();
    }
};

$(document)/*.bind("mobileinit", function () {
    $.mobile.ajaxEnabled = false;
    $.mobile.ajaxFormsEnabled = false;
})*/.ready(function () {
    $.mobile.ajaxEnabled = false;
    $.mobile.ajaxFormsEnabled = false;
        $('input[type="file"]').change(function (e) {
            var fileElement = e.target;
            $.each(fileElement.files, function (i, file) {
                FileLoader.imagePreviewCleaner(fileElement);
                // обрабатываем только картинки
                if (file.type.match('^image/.+')) {
                    FileLoader.readFile(file, function (e) {
                        FileLoader.imagePreview(e, fileElement);
                    });
                }
            });
        });
});

$.ajaxSetup({
    "complete": function () {
        $("#page").trigger("pagecreate");
    }
});
