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
    (w[c] = w[c] || []).push(function () {
        try {
            w.yaCounter26376855 = new Ya.Metrika({
                id: 26376855,
                clickmap: true,
                trackLinks: true,
                accurateTrackBounce: true
            });
        } catch (e) {
        }
    });
})(document, window, "yandex_metrika_callbacks");


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
            'v': '5.199',
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
    const swiperPrev = function () {
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

    const Page = {
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

    /**
     * @var {HTMLImageElement|undefined} img
     */
    let img = $(":mobile-pagecontainer").find('#' + $this.data('id'))[0];
    if (!img) {
        console.log('Captcha element not found');
        return;
    }

    let url = new URL(img.src);
    url.searchParams.set('n', (new Date()).getTime());

    img.src = url.toString();
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

$document.ready(function () {
    // https://github.com/commadelimited/autoComplete.js
    const attachCallback = function (settings) {
        $('li a', $(settings.target)).bind('click.autocomplete', function (e) {
            e.stopPropagation();
            e.preventDefault();
            settings.callback(e);
        });
    };
    const defaults = {
            method: 'GET',
            icon: 'arrow-r',
            cancelRequests: false,
            target: $(),
            source: null,
            callback: null,
            link: null,
            data: {},
            minLength: 0,
            transition: 'fade',
            matchFromStart: true,
            labelHTML: function (value) {
                return value;
            },
            onNoResults: function () {
                return;
            },
            onLoading: function () {
                return;
            },
            onLoadingFinished: function () {
                return;
            },
            termParam: 'term',
            loadingHtml: '<li data-icon="false"><a href="#">Searching...</a></li>',
            interval: 0,
            builder: null,
            dataHandler: null,
            klass: null,
            forceFirstChoiceOnEnterKey: true,
            transformResponse: null
        },
        openXHR = {},
        buildItems = function ($this, data, settings) {
            let str,
                vclass = '';
            if (settings.klass) {
                vclass = 'class="' + settings.klass + '"';
            }
            if (settings.builder) {
                str = settings.builder.apply($this.eq(0), [data, settings]);
            } else {
                str = [];
                if (data) {
                    if (settings.dataHandler) {
                        data = settings.dataHandler(data);
                    }
                    $.each(data, function (index, value) {
                        // are we working with objects or strings?
                        if ($.isPlainObject(value)) {
                            if (settings.transformResponse) {
                                value = settings.transformResponse(value);
                            }
                            str.push('<li ' + vclass + ' data-icon=' + settings.icon + '><a href="' + settings.link + encodeURIComponent(value.value) + '" data-transition="' + settings.transition + '" data-autocomplete=\'' + JSON.stringify(value).replace(/'/g, "&#39;") + '\'>' + settings.labelHTML(value.label) + '</a></li>');
                        } else {
                            str.push('<li ' + vclass + ' data-icon=' + settings.icon + '><a href="' + settings.link + encodeURIComponent(value) + '" data-transition="' + settings.transition + '">' + settings.labelHTML(value) + '</a></li>');
                        }
                    });
                }
            }
            if (Array.isArray(str)) {
                str = str.join('');
            }
            $(settings.target).html(str).listview("refresh");

            // is there a callback?
            if (settings.callback && typeof settings.callback === 'function') {
                attachCallback(settings);
            }

            if (str.length > 0) {
                $this.trigger("targetUpdated.autocomplete");
            } else {
                $this.trigger("targetCleared.autocomplete");

                if (settings.onNoResults) {
                    settings.onNoResults();
                }
            }
        },
        clearTarget = function ($this, $target) {
            $target.html('').listview('refresh').closest("fieldset").removeClass("ui-search-active");
            $this.trigger("targetCleared.autocomplete");
        },
        handleInput = function (e) {
            let $this = $(this),
                id = $this.attr("id"),
                text,
                data,
                settings = $this.jqmData("autocomplete"),
                element_text,
                re;

            if (e) {
                if (e.keyCode === 38) { // up
                    $('.ui-btn-active', $(settings.target))
                        .removeClass('ui-btn-active').prevAll('li.ui-btn:eq(0)')
                        .addClass('ui-btn-active').length ||
                    $('.ui-btn:last', $(settings.target)).addClass('ui-btn-active');
                } else if (e.keyCode === 40) {
                    $('.ui-btn-active', $(settings.target))
                        .removeClass('ui-btn-active').nextAll('li.ui-btn:eq(0)')
                        .addClass('ui-btn-active').length ||
                    $('.ui-btn:first', $(settings.target)).addClass('ui-btn-active');
                } else if (e.keyCode === 13 && settings.forceFirstChoiceOnEnterKey) {
                    $('.ui-btn-active a', $(settings.target)).click().length ||
                    $('.ui-btn:first a', $(settings.target)).click();
                }
            }
            if (settings) {
                // get the current text of the input field
                text = $this.val();
                // check if it's the same as the last one
                if (settings._lastText === text) {
                    return;
                }
                // store last text
                settings._lastText = text;
                // reset the timeout...
                if (settings._retryTimeout) {
                    window.clearTimeout(settings._retryTimeout);
                    settings._retryTimeout = null;
                }
                // dont change the result the user is browsing...
                if (e && (e.keyCode === 13 || e.keyCode === 38 || e.keyCode === 40)) {
                    return;
                }
                // if we don't have enough text zero out the target
                if (text.length < settings.minLength) {
                    clearTarget($this, $(settings.target));
                } else {
                    if (settings.interval && Date.now() - settings._lastRequest < settings.interval) {
                        settings._retryTimeout = window.setTimeout($.proxy(handleInput, this), settings.interval - Date.now() + settings._lastRequest);
                        return;
                    }
                    settings._lastRequest = Date.now();

                    // are we looking at a source array or remote data?
                    if (Array.isArray(settings.source)) {
                        // this function allows meta characters like +, to be searched for.
                        // Example would be C++
                        const escape = function (value) {
                            return value.replace(/[\-\[\]{}()*+?.,\\\^$|#\s]/g, "\\$&");
                        };
                        data = settings.source.sort().filter(function (element) {
                            // matching from start, or anywhere in the string?
                            if (settings.matchFromStart) {
                                // from start
                                element_text, re = new RegExp('^' + escape(text), 'i');
                            } else {
                                // anywhere
                                element_text, re = new RegExp(escape(text), 'i');
                            }
                            if ($.isPlainObject(element)) {
                                element_text = element.label;
                            } else {
                                element_text = element;
                            }
                            return re.test(element_text);
                        });
                        buildItems($this, data, settings);
                    }
                        // Accept a function as source.
                        // Function needs to call the callback, which is the first parameter.
                    // source:function(text,callback) { mydata = [1,2]; callback(mydata); }
                    else if (typeof settings.source === 'function') {
                        settings.source(text, function (data) {
                            buildItems($this, data, settings);
                        });
                    } else {
                        const ajax = {
                            type: settings.method,
                            data: settings.data,
                            dataType: 'json',
                            beforeSend: function (jqXHR) {
                                if (settings.cancelRequests) {
                                    if (openXHR[id]) {
                                        // If we have an open XML HTTP Request for this autoComplete ID, abort it
                                        openXHR[id].abort();
                                    } else {
                                    }
                                    // Set this request to the open XML HTTP Request list for this ID
                                    openXHR[id] = jqXHR;
                                }

                                if (settings.onLoading && settings.onLoadingFinished) {
                                    settings.onLoading();
                                }

                                if (settings.loadingHtml) {
                                    // Set a loading indicator as a temporary stop-gap to the response time issue
                                    $(settings.target).html(settings.loadingHtml).listview('refresh');
                                    $(settings.target).closest("fieldset").addClass("ui-search-active");
                                }
                            },
                            success: function (data) {
                                buildItems($this, data, settings);
                            },
                            complete: function () {
                                // Clear this ID's open XML HTTP Request from the list
                                if (settings.cancelRequests) {
                                    openXHR[id] = null;
                                }
                                if (settings.onLoadingFinished) {
                                    settings.onLoadingFinished();
                                }
                            }
                        };

                        if ($.isPlainObject(settings.source)) {
                            if (settings.source.callback) {
                                settings.source.callback(text, ajax);
                            }
                            for (const k in settings.source) {
                                if (k !== 'callback') {
                                    ajax[k] = settings.source[k];
                                }
                            }
                        } else {
                            ajax.url = settings.source;
                        }
                        if (settings.termParam) {
                            ajax.data[settings.termParam] = text;
                        }
                        $.ajax(ajax);
                    }
                }
            }
        },
        methods = {
            init: function (options) {
                const el = this;
                el.jqmData("autocomplete", $.extend({}, defaults, options));
                const settings = el.jqmData("autocomplete");
                return el.unbind("keyup.autocomplete")
                    .bind("keyup.autocomplete", handleInput)
                    .next('.ui-input-clear')
                    .bind('click', function () {
                        clearTarget(el, $(settings.target));
                    });
            },
            // Allow dynamic update of source and link
            update: function (options) {
                const settings = this.jqmData("autocomplete");
                if (settings) {
                    this.jqmData("autocomplete", $.extend(settings, options));
                }
                return this;
            },
            // Method to forcibly clear our target
            clear: function () {
                const settings = this.jqmData("autocomplete");
                if (settings) {
                    clearTarget(this, $(settings.target));
                }
                return this;
            },
            // Method to destroy (cleanup) plugin
            destroy: function () {
                const settings = this.jqmData("autocomplete");
                if (settings) {
                    clearTarget(this, $(settings.target));
                    this.jqmRemoveData("autocomplete");
                    this.unbind(".autocomplete");
                }
                return this;
            }
        };

    $.fn.autocomplete = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        }
    };
});

export {Autocomplete, Helper};
