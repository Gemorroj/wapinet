"use strict";

const FileSwiper = {
    pageCreate: function () {
        let $pageContainer = $(":mobile-pagecontainer");

        let swiperNext = function () {
            let $link = $(this);

            $pageContainer.pagecontainer("change", $link.attr('href'), {
                "transition": "slide"
            });
            return false;
        };
        let swiperPrev = function () {
            let $link = $(this);

            $pageContainer.pagecontainer("change", $link.attr('href'), {
                "transition": "slide",
                "reverse": true
            });
            return false;
        };

        $pageContainer
            .on("swipeleft", function () {
                $(this).find("a[data-id='swipe-next']").filter(":visible").click();
            })
            .on("swiperight", function () {
                $(this).find("a[data-id='swipe-prev']").filter(":visible").click();
            })
            .on("click", "a[data-id='swipe-next']", swiperNext)
            .on("click", "a[data-id='swipe-prev']", swiperPrev)
            .on("click", ".image-size-btn", function () {
                $pageContainer.css("background-size", ($pageContainer.css("background-size") === 'cover' ? 'contain' : 'cover'));
            })
        ;
    }
};

export default FileSwiper;
