"use strict";

const FileSwiper = {
    pageCreate: function () {
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

        $pageContainer
            .on("swipeleft", $pageContainer, swiperNext)
            .on("swiperight", $pageContainer, swiperPrev)
            .on("click", "#swiper-next", swiperNext)
            .on("click", "#swiper-prev", swiperPrev)
            .on("click", ".image-size-btn", function () {
                $pageContainer.css("background-size", ($pageContainer.css("background-size") === 'cover' ? 'contain' : 'cover'));
            })
        ;
    }
};
