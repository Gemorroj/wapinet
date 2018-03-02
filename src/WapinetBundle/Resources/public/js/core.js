"use strict";

var Loader = {
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


var Autocomplete = {
    text: function (source, $input, $suggestions) {
        $input.autocomplete({
            link: '#',
            target: $suggestions,
            source: source,
            loadingHtml: '',
            icon: 'tag',
            callback: function (e) {
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
    }
};

var Templating = {
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

var Vk = {
    data: null,
    show: function (data) {
        if (data && (data.response || data.error)) {
            Vk.data = data;
            $(":mobile-pagecontainer").on("click", "#user-vk", Vk.popup);
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


var $document = $(document);


// яндекс метрика
(function (d, w, c) {
    (w[c] = w[c] || []).push(function() {
        try {
            w.yaCounter26376855 = new Ya.Metrika({
                id: 26376855,
                clickmap: true,
                trackLinks: true,
                accurateTrackBounce: true
            });
        } catch(e) { }
    });
})(document, window, "yandex_metrika_callbacks");


// jplayer
window.Jplayer = {
    'swfPath': "//cdnjs.cloudflare.com/ajax/libs/jplayer/2.9.2/jplayer/jquery.jplayer.swf"
};


// hypercomments
var _commentsLoader = function ($pageContainer, xid) {
    var $commentsContainer = $pageContainer.find("#hypercomments_widget");
    $commentsContainer.empty();

    var hcInterval = window.setInterval(function () {
        if ("HC" in window) {
            window.clearInterval(hcInterval);
            var title = $pageContainer.find('#header-title').html();

            HC.widget("Stream", {
                "hc_disable": 1,
                "xid": xid,
                "widget_id": 76882,
                "title": title,
                "href": window.location.pathname,
                "like_href": window.location.href,
                "like_title": title,
                "append": $commentsContainer[0]
            });
        }
    }, 100);
};

$document.on("pageshow", "#file_view", function () {
    _commentsLoader(
        $(this),
        "file-" + window.location.pathname.split("/").reverse()[0]
    );
}).on("pageshow", "#gist_view", function () {
    _commentsLoader(
        $(this),
        "gist-" + window.location.pathname.split("/").reverse()[0]
    );
}).on("pageshow", "#news_show", function () {
    _commentsLoader(
        $(this),
        "news-" + window.location.pathname.split("/").reverse()[0]
    );
});

$document.on("pagecreate", "#file_index", function () {
    var $commentsContainer = $(":mobile-pagecontainer").find("#hypercomments_mix");
    $commentsContainer.empty();

    var hcInterval = window.setInterval(function () {
        if ("HC" in window) {
            window.clearInterval(hcInterval);
            HC.widget("Mixstream", {
                "widget_id": 76882,
                "filter": "last",
                "limit": 5,
                "append": $commentsContainer[0]
            }, "add");
        }
    }, 100);
});



$document.one("pagecreate", "#file_upload", function () {
    FileUpload.pageCreate();
});
$document.one("pagecreate", "#file_edit", function () {
    FileEdit.pageCreate();
});
$document.one("pagecreate", "#file_view", function () {
    FileView.pageCreate();
}).on("pageshow", "#file_view", function () {
    FileView.pageShow($(this));
});
$document.one("pagecreate", "#file_swiper", function () {
    FileSwiper.pageCreate();
});


$document.one("pagecreate", "#archiver_edit", function () {
    Archiver.pageCreate();
});

$document.one("pagecreate", "#php_obfuscator_index", function () {
    PhpObfuscator.pageCreate();
});

$document.one("pagecreate", "#translate_index", function () {
    Translate.pageCreate();
});

$document.one("pagecreate", "#http_index", function () {
    Http.pageCreate();
});

$document.one("pagecreate", "#guestbook_index", function () {
    Guestbook.pageCreate();
});

$document.one("pagecreate", "#gist_view", function () {
    Gist.pageCreate();
});


// vk в профиле пользователя
$document.one("pagecreate", "#fos_user_profile_show", function () {
    var vkId = $(":mobile-pagecontainer").find("#user-vk").data("id");

    if (vkId) {
        $.getScript('https://api.vk.com/method/users.get?callback=Vk.show&fields=online,photo_200_orig&user_ids=' + vkId);
    }
});


// подгрузка картинок в попапах
$document.on("click", "a[href^='#image-']", function () {
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


// свайпер в пагинации
$document.on("click", ".pagerfanta a", function () {
    var $pageContainer = $(":mobile-pagecontainer");
    var that = this;
    var $this = $(that);

    var swiperNext = function () {
        $pageContainer.pagecontainer("change", $this.attr('href'), {
            "transition": "slide"
        });
        return false;
    };
    var swiperPrev = function () {
        $pageContainer.pagecontainer("change", $this.attr('href'), {
            "transition": "slide",
            "reverse": true
        });
        return false;
    };


    if ($this.hasClass("ui-first-child")) {
        return swiperPrev();
    }
    if ($this.hasClass("ui-last-child")) {
        return swiperNext();
    }

    var Page = {
        "all": 0,
        "current": 0,
        "click": 0
    };
    $this.closest("div").find("a").not(".ui-first-child, .ui-last-child").each(function () {
        Page.all++;

        if (that === this) {
            Page.click = Page.all;
        }
        if ($(this).hasClass("page-current")) {
            Page.current = Page.all;
        }
    });

    if (Page.click > Page.current) {
        return swiperNext();
    }
    if (Page.click < Page.current) {
        return swiperPrev();
    }
});


// предпросмотр картинок в загружаемых файлах
$document.on("change", 'input[type="file"]', function (e) {
    var fileElement = e.target;
    // только если поддерживается
    if (fileElement.files) {
        var file = fileElement.files[0];
        Loader.previewCleaner(fileElement);
        Loader.readFile(file, function (e) {
            Loader.preview(e, file, fileElement);
        });

    }
});


// обновление капчи
$document.on("click", 'a.captcha-reload', function () {
    var $this = $(this);

    var img = $(":mobile-pagecontainer").find('#' + $this.data('id'))[0];
    img.src = $this.data('path') + '?n=' + (new Date()).getTime();
});


// выключение взаимозаменяющих полей в url_file
$document.on("change", "fieldset.file-url input[type='file']", function (e) {
    var state = 'enable';
    if (e.target.value) {
        state = 'disable';
    }
    $(":mobile-pagecontainer").find('fieldset.file-url input[type="url"]').textinput(state);
}).on("change", "fieldset.file-url input[type='url']", function (e) {
    var state = 'enable';
    if (e.target.value) {
        state = 'disable';
    }
    $(":mobile-pagecontainer").find('fieldset.file-url input[type="file"]').textinput(state);
});


// лоадер на ajax запросах
$document.ajaxError(function () {
    $.mobile.loading("show", {
        html: '<h1 class="red">Ошибка</h1>',
        textVisible: true,
        textonly: true
    });
    setTimeout(function () {
        $.mobile.loading('hide');
    }, 5000);
}).ajaxStart(function () {
    $.mobile.loading('show');
}).ajaxSuccess(function (data) {
    // заголовок
    var $title = $(data).find('title');
    if ($title.length) {
        Templating.setTitle($title.html()).render();
    }

    $.mobile.loading('hide');
});
