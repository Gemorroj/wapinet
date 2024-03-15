"use strict";

import PhpObfuscator from "./module/php_obfuscator";
import Http from "./module/http";
import Guestbook from "./module/guestbook";
import Gist from "./module/gist";
import Archiver from "./module/archiver";
import FileEdit from "./module/file/edit";
import FileSwiper from "./module/file/swiper";
import FileUpload from "./module/file/upload";
import FileView from "./module/file/view";


const Loader = {
    readFile: function (file, callback) {
        if (FileReader) {
            let fileReader = new FileReader();
            fileReader.onload = callback;
            fileReader.readAsDataURL(file);
            return fileReader;
        }
        return false;
    },
    preview: function (e, file, previewElement) {
        let container = document.createElement('p');
        container.className = 'container-preview';

        if (file.type.match('^image/.+')) {
            let img = new Image();
            img.src = e.target.result;
            img.className = 'image-preview';
            container.appendChild(img);
        }

        let size = document.createTextNode(' ' + Helper.sizeFormat(e.total));
        container.appendChild(size);

        $(previewElement).parent().prepend(container);
    },
    previewCleaner: function (previewElement) {
        $(previewElement).parent().find('p.container-preview').remove();
    }
};

const Helper = {
    /**
     * @param {Blob} blob
     * @param {string} name
     */
    saveAs: function (blob, name) {
        const a = document.createElementNS('http://www.w3.org/1999/xhtml', 'a');
        a.download = name;
        a.rel = 'noopener';
        a.href = URL.createObjectURL(blob);

        setTimeout(() => URL.revokeObjectURL(a.href), 40 /* sec */ * 1000);
        setTimeout(() => a.click(), 0);
    },
    sizeFormat: function (fileSizeInBytes) {
        let i = -1;
        const byteUnits = [' kB', ' MB', ' GB', ' TB', 'PB', 'EB', 'ZB', 'YB'];
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
        const blob = new Blob(
            [text],
            {"type": "text/plain;charset=utf-8"}
        );
        // saveAs реализована в FileSaver
        Helper.saveAs(blob, fileName || "file.txt");
    }
};


const Autocomplete = {
    text: function (source, $input, $suggestions) {
        $input.autocomplete({
            link: '#',
            target: $suggestions,
            source: source,
            loadingHtml: '',
            icon: 'tag',
            callback: function (e) {
                let arrText = $input.val().split(',');

                arrText.pop();
                arrText.push($(e.currentTarget).text());

                let text = arrText.join(',');

                $input.val(text);
                $input.autocomplete('clear');
            },
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
        let title = Templating.getTitle();

        if (title !== null) {
            $(":mobile-pagecontainer").find('#header-title').text(title);
        }
    }
};

const Vk = {
    /*
{
    "response": [
        {
            "first_name": "Lindsey",
            "id": 210700286,
            "last_name": "Stirling",
            "can_access_closed": true,
            "is_closed": false,
            "online": 0,
            "photo_200_orig": "https://example.com"
        }
    ]
}
     */
    data: null,
    show: function (data) {
        if (data && (data.response || data.error)) {
            Vk.data = data;
            $(":mobile-pagecontainer").on("click", "#user-vk", Vk.popup);
        }
    },
    popup: function () {
        let $link = $(this);
        let $popup = $($link.attr("href"));
        let content = Vk._makeContent();

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
            let user = Vk.data.response[0];

            let str = `
                <a rel="external" href="https://vk.com/id${user.id}">
                    <img src="${user.photo_200_orig}" />
                </a><br />
                <span>${user.first_name} ${user.last_name}</span><br />
            `;

            return str + (user.online ? '<span class="green">Онлайн</span>' : '<span class="gray">Офлайн</span>');
        }

        return 'No data';
    }
};


const $document = $(document);


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
    "swfPath": "//cdnjs.cloudflare.com/ajax/libs/jplayer/2.9.2/jplayer/jquery.jplayer.swf"
};


// vk comments
const _commentsLoader = function ($pageContainer, xid) {
    let $commentsContainer = $pageContainer.find("#vkcomments_widget");
    $commentsContainer.empty();

    const id = 'vkcomments_widget-' + new Date().getTime();
    $commentsContainer.attr('id', id); // заменяем id на уникальный для API вконтакте

    let i = 0;
    let vkCommentsInterval = window.setInterval(function () {
        i++;
        if ("VK" in window) {
            if (!VK._apiId) {
                VK.init(window.vkInitConfig);
            }
            window.clearInterval(vkCommentsInterval);
            VK.Widgets.Comments(id, {}, xid);
        } else if (i > 100) {
            window.clearInterval(vkCommentsInterval);
            console.log('Can\'t initialize VK');
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
$document.one("pagecreate", "#wapinet_user_profile", function () {
    let vkId = $(":mobile-pagecontainer").find("#user-vk").data("id");

    if (vkId) {
        $.post(Routing.generate('vk_api_users_get'), {
            'v': '5.131',
            'fields': 'online,photo_200_orig',
            'user_ids': vkId
        }, Vk.show, 'json');
    }
});


// подгрузка картинок в попапах
$document.on("click", "a[href^='#image-']", function () {
    $.mobile.loading("show");

    let popup = $(this.getAttribute('href'));
    let img = popup.find("img");

    let src = img.attr('src');
    let srcData = img.data('src');

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
    let $pageContainer = $(":mobile-pagecontainer");
    let that = this;
    let $this = $(that);

    let swiperNext = function () {
        $pageContainer.pagecontainer("change", $this.attr('href'), {
            "transition": "slide"
        });
        return false;
    };
    let swiperPrev = function () {
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

    let Page = {
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
    let fileElement = e.target;
    // только если поддерживается
    if (fileElement.files) {
        let file = fileElement.files[0];
        Loader.previewCleaner(fileElement);
        Loader.readFile(file, function (e) {
            Loader.preview(e, file, fileElement);
        });

    }
});


// обновление капчи
$document.on("click", 'a.captcha-reload', function () {
    let $this = $(this);

    let img = $(":mobile-pagecontainer").find('#' + $this.data('id'))[0];
    img.src = $this.data('path') + '?n=' + (new Date()).getTime();
});


// выключение взаимозаменяющих полей в url_file
$document.on("change", "fieldset.file-url input[type='file']", function (e) {
    let state = 'enable';
    if (e.target.value) {
        state = 'disable';
    }
    $(":mobile-pagecontainer").find('fieldset.file-url input[type="url"]').textinput(state);
}).on("change", "fieldset.file-url input[type='url']", function (e) {
    let state = 'enable';
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
    let $title = $(data).find('title');
    if ($title.length) {
        Templating.setTitle($title.html()).render();
    }

    $.mobile.loading('hide');
});

export {Autocomplete, Helper};
