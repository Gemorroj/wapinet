"use strict";

const FileLoader = {
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
    }
};

const Helper = {
    sizeFormat: function (fileSizeInBytes) {
        var i = -1;
        var byteUnits = [' kB', ' MB', ' GB', ' TB', 'PB', 'EB', 'ZB', 'YB'];
        do {
            fileSizeInBytes = fileSizeInBytes / 1024;
            i++;
        } while (fileSizeInBytes > 1024);

        return Math.max(fileSizeInBytes, 0.1).toFixed(1) + byteUnits[i];
    },
    escapeHtml: function (str) {
        return str.toString().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    },
    escapeUrl: function (str) {
        return window.encodeURI(str.toString());
    },
    downloadText: function (text, fileName) {
        var blob = new Blob(
            [text],
            {"type": "text/plain;charset=utf-8"}
        );
        // saveAs реализована в FileSaver
        saveAs(blob, fileName || "file.txt");
    }
};


const Autocomplete = {
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

const Templating = {
    /**
     * @private
     */
    _title: null,
    /**
     * @param {String} title
     * @returns {Templating}
     */
    setTitle: function (title) {
        Templating._title = title;
        return this;
    },
    /**
     * @returns {null|String}
     */
    getTitle: function () {
        return Templating._title;
    },
    render: function () {
        var title = Templating.getTitle();

        if (title !== null) {
            $(":mobile-pagecontainer").find('#header-title').text(title);
        }
    }
};

const Vk = {
    data: null,
    show: function (data) {
        if (data && (data.response || data.error)) {
            Vk.data = data;
            $(":mobile-pagecontainer").find("#user-vk").click(Vk.popup);
        }
    },
    popup: function () {
        var $link = $(this);
        var $popup = $($link.attr("href"));
        var content = Vk._makeContent();

        $popup.find('p').html(content);
        $popup.popup("open", {"transition": "pop", "positionTo": $link});

        return false;
    },
    _makeContent: function () {
        if (!Vk.data) {
            return 'Error';
        }

        if (Vk.data.error && Vk.data.error.error_code) {
            return Vk.data.error.error_msg;
        }

        if (Vk.data.response && Vk.data.response[0]) {
            var user = Vk.data.response[0];
            return '<a rel="external" href="https://vk.com/id' + user.uid + '"><img src="' + user.photo_200_orig + '" /></a><br/><span>' + user.first_name + ' ' + user.last_name + '</span><br/>' + (user.online ? '<span class="green">Онлайн</span>' : '<span class="gray">Офлайн</span>');
        }

        return 'No data';
    }
};
