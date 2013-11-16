var FileLoader = {
    readFile: function (file, callback) {
        if (window.FileReader && file.type.match('^image/.+')) {
            var fileReader = new window.FileReader();
            fileReader.onload = callback;
            fileReader.readAsDataURL(file);
            return fileReader;
        }
        return false;
    },
    preview: function (e, previewElement) {
        var img = new Image();
        img.src = e.target.result;
        img.className = 'image_preview';

        var $prevParent = $(previewElement).parent();
        $prevParent.find('img.image_preview').remove();
        $prevParent.prepend(img);
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
                FileLoader.readFile(file, function (e) {
                    FileLoader.preview(e, fileElement);
                });
            });
        });
});

$.ajaxSetup({
    "complete": function () {
        $("#page").trigger("pagecreate");
    }
});
