"use strict";

const Guestbook = {
    pageCreate: function () {
        let $pageContainer = $(":mobile-pagecontainer");

        let quote = function () {
            let $link = $(this);
            let $textarea = $pageContainer.find("textarea[name='message_form[message]']").filter(":visible");
            $textarea.focus();

            let $liComment = $link.parent().next();
            let text = $liComment.children('div').text();
            let author = $link.next('a').text();
            $textarea.val('[quote=' + author + ']' + text + '[/quote]');
            $textarea.keyup();

            return false;
        };
        let popup = function () {
            let $link = $(this);
            let selector = $link.attr('href');

            $pageContainer.find(selector).popup("open", {"transition": "turn", "positionTo": $link});

            return false;
        };

        $pageContainer.on("click", "a[href^='#popup-']", popup);
        $pageContainer.on("click", "a.item-quote", quote);
    }
};

export default Guestbook;
