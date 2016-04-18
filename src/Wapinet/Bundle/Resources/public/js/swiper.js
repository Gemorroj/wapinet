"use strict";

$(document).one("pagecreate", ".swiper-page", function () {
    var $pageContainer = $(":mobile-pagecontainer");

    var swiperNext = function () {
        var $this = $(this);
        if (!$this.is('a')) {
            $this = $this.find('#swiper-next');
        }
        $pageContainer.pagecontainer("change", $this.attr('href'), {
            "transition": "slide"
        });
        return false;
    };
    var swiperPrev = function () {
        var $this = $(this);
        if (!$this.is('a')) {
            $this = $this.find('#swiper-prev');
        }
        $pageContainer.pagecontainer("change", $this.attr('href'), {
            "transition": "slide",
            "reverse": true
        });
        return false;
    };

    $(document)
        .on("swipeleft", ".swiper-page", swiperNext)
        .on("swiperight", ".swiper-page", swiperPrev)
        .on("click", "#swiper-next", swiperNext)
        .on("click", "#swiper-prev", swiperPrev)
        .on("click", ".image-size-btn", function () {
            var $page = $(this).closest(".swiper-page");
            $page.css("background-size", ($page.css("background-size") === 'cover' ? 'contain' : 'cover'));
        })
    ;
});
