var isTouchDevice = ('ontouchstart' in document.documentElement);

$(document).one("pagecreate", "#page", function () {
    var $pageContainer = $(":mobile-pagecontainer");

    var swipeNext = function ($link) {
        if ($link.hasClass("ui-disabled")) {
            return;
        }
        $pageContainer.pagecontainer("change", $link.attr('href'), {
            "transition": "slide"
        });
    };

    var swipePrev = function ($link) {
        if ($link.hasClass("ui-disabled")) {
            return;
        }
        $pageContainer.pagecontainer("change", $link.attr('href'), {
            "transition": "slide",
            "reverse": true
        });
    };

    $(document).on("click", "nav", function (e) {
        var $current = $(this).find("a[href='#']");
        var $click = $(e.target);

        if (!$click.attr("href")) {
            return;
        }

        if ($current.text() === "Пред.1" || $click.attr("href").split("=").splice(-1) > $current.text()) {
            swipeNext($click);
        } else {
            swipePrev($click);
        }

        return false;
    });

    if (isTouchDevice) {
        $(document).on("swipeleft", "nav", function () {
            var $next = $(this)
                .find("a")
                .contents()
                .filter(function () {
                    return ($(this).text() === "След.");
                }).parent();

            swipeNext($next);
        });

        $(document).on("swiperight", "nav", function () {
            var $prev = $(this)
                .find("a")
                .contents()
                .filter(function () {
                    return ($(this).text() === "Пред.");
                }).parent();

            swipePrev($prev);
        });
    }
});
